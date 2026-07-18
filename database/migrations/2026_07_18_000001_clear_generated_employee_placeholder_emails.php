<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userIds = DB::table('users')
            ->whereRaw("LOWER(email) LIKE '%@placeholder.local'")
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('employees')
            ->whereIn('user_id', $userIds)
            ->whereRaw("LOWER(COALESCE(email, '')) LIKE '%@placeholder.local'")
            ->update(['email' => null]);

        DB::table('users')
            ->whereIn('id', $userIds)
            ->update(['email' => null]);
    }

    public function down(): void
    {
        // Generated placeholder addresses are intentionally not recreated.
    }
};
