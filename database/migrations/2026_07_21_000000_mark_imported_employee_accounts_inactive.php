<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userIds = DB::table('users')
            ->join('employees', 'employees.user_id', '=', 'users.id')
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = 'employee'")
            ->whereNull('users.email')
            ->pluck('users.id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $userIds->all())
            ->update([
                'status' => 'Not Approved',
                'account_status' => 'Inactive',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Approval and activation are administrative decisions and are not restored automatically.
    }
};
