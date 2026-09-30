<?php

namespace App\Support;

use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;

class EmployeeLeaveCredits
{
    public function forMonth(User $user, Carbon $month): array
    {
        $teaching = strcasecmp((string) ($user->employee?->job_type ?? ''), 'Teaching') === 0;
        $hired = $teaching ? $user->applicant?->date_hired : null;
        $hired = $hired ?: $user->employee?->employement_date ?: $user->applicant?->date_hired;
        $limit = 0.0;
        $vacationUsed = 0.0;
        $sickUsed = 0.0;
        $hasApproved = false;
        $cutoff = $month->copy()->endOfMonth()->min(now()->endOfDay());
        if ($hired) {
            $start = Carbon::parse($hired)->addYear()->startOfDay();
            if ($cutoff->gte($start)) {
                $cycle = $teaching ? 10 : 12;
                $months = (int) $start->copy()->startOfMonth()->diffInMonths($cutoff->copy()->startOfMonth()) + 1;
                $cycleOffset = intdiv($months - 1, $cycle) * $cycle;
                $cycleStart = $start->copy()->startOfMonth()->addMonths($cycleOffset);
                $limit = round(($months - $cycleOffset) / 2, 1);
                $approved = LeaveApplication::query()
                    ->where('user_id', $user->id)
                    ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
                    ->whereBetween('created_at', [$cycleStart, $cutoff])
                    ->get();
                $hasApproved = $approved->isNotEmpty();
                $vacationUsed = round((float) $approved->sum('applied_vacation'), 1);
                $sickUsed = round((float) $approved->sum('applied_sick'), 1);
            }
        }

        return [
            'limit' => $limit,
            'vacation_used' => $vacationUsed,
            'sick_used' => $sickUsed,
            'vacation' => round(max($limit - $vacationUsed, 0), 1),
            'sick' => round(max($limit - $sickUsed, 0), 1),
            'has_approved' => $hasApproved,
        ];
    }
}
