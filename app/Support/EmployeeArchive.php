<?php

namespace App\Support;

use App\Models\User;

class EmployeeArchive
{
    public static function isResigned(User $user): bool
    {
        $resignation = $user->resignations
            ->filter(fn ($record) => in_array(strtolower(trim($record->status)), ['approved', 'completed'], true))
            ->sortByDesc(fn ($record) => ($record->effective_date ?? $record->processed_at ?? $record->submitted_at ?? $record->created_at)?->timestamp ?? 0)
            ->first();
        if (!$resignation) return false;

        $hire = $user->applicant;
        if (!$hire) return true;
        $processed = $resignation->processed_at ?? $resignation->submitted_at ?? $resignation->created_at;
        $rehired = ($processed && $hire->created_at?->gt($processed))
            || ($processed && $hire->date_hired?->toDateString() > $processed->toDateString())
            || ($resignation->effective_date && $hire->date_hired?->toDateString() > $resignation->effective_date->toDateString());
        return !$rehired;
    }
}
