<?php

namespace App\Support;

use App\Mail\EmployeeAccountInactiveMail;
use App\Models\Applicant;
use App\Models\LeaveApplication;
use App\Models\Resignation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmployeeAccountStatusManager
{
    public function syncAllEmployeeStatuses(?Carbon $targetDate = null): int
    {
        $synced = 0;
        $date = ($targetDate ?? Carbon::today())->copy()->startOfDay();

        User::query()
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'email', 'role', 'status', 'account_status'])
            ->chunkById(100, function ($users) use (&$synced, $date) {
                foreach ($users as $user) {
                    $this->syncUserAccountStatus($user, $date);
                    $synced++;
                }
            });

        return $synced;
    }

    public function syncUserAccountStatus(User|int $user, ?Carbon $targetDate = null): string
    {
        $model = $user instanceof User
            ? $user
            : User::query()->find((int) $user);

        if (!$model) {
            return 'Active';
        }

        $resolvedStatus = $this->resolveAccountStatus($model, $targetDate);
        $previousStatus = trim((string) ($model->account_status ?? ''));

        if ($previousStatus !== $resolvedStatus) {
            $model->forceFill([
                'account_status' => $resolvedStatus,
            ])->save();

            if (
                strcasecmp($resolvedStatus, 'Inactive') === 0
                && strcasecmp($previousStatus, 'Inactive') !== 0
            ) {
                $this->queueInactiveAccountEmail($model->fresh() ?? $model);
            }
        }

        return $resolvedStatus;
    }

    public function resolveAccountStatus(User|int $user, ?Carbon $targetDate = null): string
    {
        $model = $user instanceof User
            ? $user
            : User::query()->find((int) $user);

        if (!$model) {
            return 'Active';
        }

        $userId = (int) $model->id;
        if ($userId <= 0) {
            return 'Active';
        }

        if (strcasecmp(trim((string) ($model->status ?? '')), 'Not Approved') === 0) {
            return 'Inactive';
        }

        $latestApprovedOrCompletedResignation = Resignation::query()
            ->where('user_id', $userId)
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) IN (?, ?)", ['approved', 'completed'])
            ->orderByDesc(DB::raw('COALESCE(effective_date, processed_at, submitted_at, created_at)'))
            ->orderByDesc('id')
            ->first();

        if ($latestApprovedOrCompletedResignation) {
            $latestResignationDate = $latestApprovedOrCompletedResignation->processed_at
                ?? $latestApprovedOrCompletedResignation->submitted_at
                ?? $latestApprovedOrCompletedResignation->created_at;
            $latestResignationEffectiveDate = $latestApprovedOrCompletedResignation->effective_date;

            $rehiredAfterResignation = Applicant::query()
                ->where('user_id', $userId)
                ->whereRaw("LOWER(TRIM(COALESCE(application_status, ''))) = ?", ['hired'])
                ->where(function ($query) use ($latestResignationDate, $latestResignationEffectiveDate) {
                    $query->where('created_at', '>', $latestResignationDate);

                    if ($latestResignationDate) {
                        $query->orWhere('date_hired', '>', Carbon::parse($latestResignationDate)->toDateString());
                    }

                    if ($latestResignationEffectiveDate) {
                        $query->orWhere('date_hired', '>', Carbon::parse($latestResignationEffectiveDate)->toDateString());
                    }
                })
                ->exists();

            if (!$rehiredAfterResignation) {
                return 'Inactive';
            }
        }

        if (strcasecmp(trim((string) ($model->role ?? '')), 'employee') !== 0) {
            return trim((string) ($model->account_status ?? '')) !== ''
                ? (string) $model->account_status
                : 'Active';
        }

        $comparisonDate = ($targetDate ?? Carbon::today())->copy()->startOfDay();
        $hasApprovedLeave = LeaveApplication::query()
            ->where('user_id', $userId)
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
            ->get()
            ->contains(fn (LeaveApplication $leaveApplication) => $this->isLeaveApplicationActiveOnDate($leaveApplication, $comparisonDate));

        return $hasApprovedLeave ? 'On Leave' : 'Active';
    }

    public function isLeaveApplicationActiveOnDate(LeaveApplication $leaveApplication, ?Carbon $targetDate = null): bool
    {
        [$startDate, $endDate] = $this->resolveLeaveApplicationDateRange($leaveApplication);
        if (!$startDate || !$endDate) {
            return false;
        }

        $comparisonDate = ($targetDate ?? Carbon::today())->copy()->startOfDay();
        return $comparisonDate->betweenIncluded($startDate, $endDate);
    }

    public function resolveLeaveApplicationDateRange(LeaveApplication $leaveApplication): array
    {
        $parseDate = static function ($value): ?Carbon {
            $text = trim((string) ($value ?? ''));
            if ($text === '') {
                return null;
            }

            foreach (['Y-m-d', 'm/d/Y', 'n/j/Y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $text)->startOfDay();
                } catch (\Throwable $e) {
                }
            }

            try {
                return Carbon::parse($text)->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        };

        $inclusiveDates = trim((string) ($leaveApplication->inclusive_dates ?? ''));
        $matchedDates = [];
        if ($inclusiveDates !== '') {
            preg_match_all('/\b\d{4}-\d{2}-\d{2}\b|\b\d{1,2}\/\d{1,2}\/\d{4}\b/', $inclusiveDates, $matches);
            $matchedDates = $matches[0] ?? [];
        }

        $startDate = isset($matchedDates[0]) ? $parseDate($matchedDates[0]) : null;
        $endDate = isset($matchedDates[1]) ? $parseDate($matchedDates[1]) : null;

        if (!$startDate) {
            $startDate = $parseDate($leaveApplication->filing_date) ?: $parseDate($leaveApplication->created_at);
        }

        $days = (float) ($leaveApplication->number_of_working_days ?? 0);
        if ($days <= 0) {
            $days = max(
                (float) ($leaveApplication->days_with_pay ?? 0),
                (float) ($leaveApplication->days_without_pay ?? 0),
                (float) ($leaveApplication->applied_total ?? 0)
            );
        }

        if (!$endDate && $startDate) {
            $rangeDays = max((int) ceil($days), 1);
            $endDate = $startDate->copy()->addDays($rangeDays - 1);
        }

        if ($startDate && $endDate && $endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        return [$startDate, $endDate];
    }

    private function queueInactiveAccountEmail(User $user): void
    {
        $recipient = trim((string) ($user->email ?? ''));
        if ($recipient === '') {
            return;
        }

        try {
            Mail::to($this->mailToAddress($recipient))
                ->queue(new EmployeeAccountInactiveMail($user));
        } catch (\Throwable $exception) {
            Log::warning('Employee account marked inactive but notification email could not be queued.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'to_override' => config('mail.to_override'),
                'account_status' => $user->account_status,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function mailToAddress(string $recipient): string
    {
        $override = trim((string) config('mail.to_override'));

        return $override !== '' ? $override : $recipient;
    }
}
