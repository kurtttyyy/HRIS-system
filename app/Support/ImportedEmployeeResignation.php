<?php

namespace App\Support;

use App\Models\Resignation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportedEmployeeResignation
{
    public function record(User $user, string $date, array $details): void
    {
        DB::transaction(function () use ($user, $date, $details) {
            $reason = 'Imported from the employee 201 file.';
            $resignation = Resignation::query()
                ->where('user_id', $user->id)
                ->whereDate('effective_date', $date)
                ->where('reason', $reason)
                ->first() ?? new Resignation();
            $resignation->fill(array_merge($details, [
                'user_id' => $user->id,
                'effective_date' => $date,
                'reason' => $reason,
                'status' => 'Approved',
            ]))->save();

            // A dated resignation also disables an already activated account.
            // Removing its PIN prevents a former employee activating it again.
            User::withoutEvents(fn () => $user->forceFill([
                'account_status' => 'Inactive',
                'temporary_pin' => null,
            ])->save());
        });
    }
}
