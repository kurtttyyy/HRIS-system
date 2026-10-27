<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

class ReportPeriod
{
    public static function employeeJoinedIn(User $user, Carbon $start, Carbon $end): bool
    {
        $date = $user->employee?->employement_date ?: ($user->applicant?->date_hired ?: $user->created_at);
        if (!$date) return false;
        try {
            return Carbon::parse($date)->betweenIncluded($start, $end);
        } catch (\Throwable $error) {
            return false;
        }
    }
}
