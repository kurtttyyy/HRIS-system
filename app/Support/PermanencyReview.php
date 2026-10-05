<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

class PermanencyReview
{
    public function department(User $user): string
    {
        foreach ([$user->department, $user->employee?->department, $user->applicant?->position?->department] as $value) {
            if (trim((string) $value) !== '') return trim((string) $value);
        }
        return '';
    }

    public function dueDate(User $user): ?Carbon
    {
        $employee = $user->employee;
        if (!$employee || !str_contains(strtolower(trim((string) $employee->classification)), 'probationary')) return null;
        $joined = $employee->employement_date ?? $user->applicant?->date_hired;
        if (!$joined) return null;
        $jobType = strtolower(trim((string) ($employee->job_type ?: $user->applicant?->position?->job_type)));
        try {
            $date = Carbon::parse($joined)->startOfDay();
            return in_array($jobType, ['non-teaching', 'non teaching', 'nt', 'nonteaching'], true)
                ? $date->addMonthsNoOverflow(6) : $date->addYears(3);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function eligible(User $head, User $employee, int $noticeDays): bool
    {
        $department = $this->department($head);
        $due = $this->dueDate($employee);
        return strtolower(trim((string) $head->role)) === 'employee'
            && strtolower(trim((string) $head->status)) === 'approved'
            && strtolower(trim((string) $head->department_head)) === 'approved'
            && $head->id !== $employee->id
            && strtolower(trim((string) $employee->role)) === 'employee'
            && strtolower(trim((string) $employee->status)) === 'approved'
            && $department !== ''
            && strcasecmp($department, $this->department($employee)) === 0
            && $due !== null
            && now()->startOfDay()->gte($due->copy()->subDays($noticeDays));
    }
}
