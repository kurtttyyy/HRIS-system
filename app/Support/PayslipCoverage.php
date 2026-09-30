<?php

namespace App\Support;

use App\Models\PayslipRecord;
use Illuminate\Support\Collection;

class PayslipCoverage
{
    public function countEmployees(Collection $employees): int
    {
        $userIds = PayslipRecord::query()
            ->whereIn('user_id', $employees->pluck('id'))
            ->distinct()->pluck('user_id')->mapWithKeys(fn ($id) => [(string) $id => true]);
        $employeeIds = $employees->map(fn ($user) => trim((string) $user->employee?->employee_id))
            ->filter()->unique()->values();
        $unlinkedIds = PayslipRecord::query()
            ->whereNull('user_id')
            ->whereIn('employee_id', $employeeIds)
            ->distinct()->pluck('employee_id')->mapWithKeys(fn ($id) => [(string) $id => true]);

        return $employees->filter(fn ($user) => $userIds->has((string) $user->id)
            || $unlinkedIds->has(trim((string) $user->employee?->employee_id)))->count();
    }
}
