<?php

namespace App\Support;

use App\Models\PayslipRecord;
use Illuminate\Support\Collection;

class PayslipCoverage
{
    public function countEmployees(Collection $employees, ?\Illuminate\Support\Carbon $start = null, ?\Illuminate\Support\Carbon $end = null): int
    {
        $userIds = PayslipRecord::query()
            ->when($start && $end, fn ($query) => $query->whereBetween('created_at', [$start, $end]))
            ->whereIn('user_id', $employees->pluck('id'))
            ->distinct()->pluck('user_id')->mapWithKeys(fn ($id) => [(string) $id => true]);
        $employeeIds = $employees->map(fn ($user) => trim((string) $user->employee?->employee_id))
            ->filter()->unique()->values();
        $unlinkedIds = PayslipRecord::query()
            ->when($start && $end, fn ($query) => $query->whereBetween('created_at', [$start, $end]))
            ->whereNull('user_id')
            ->whereIn('employee_id', $employeeIds)
            ->distinct()->pluck('employee_id')->mapWithKeys(fn ($id) => [(string) $id => true]);

        return $employees->filter(fn ($user) => $userIds->has((string) $user->id)
            || $unlinkedIds->has(trim((string) $user->employee?->employee_id)))->count();
    }
}
