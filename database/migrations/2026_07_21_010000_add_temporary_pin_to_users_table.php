<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('temporary_pin')->nullable()->after('password');
        });

        $userIds = DB::table('users')
            ->join('employees', 'employees.user_id', '=', 'users.id')
            ->whereNull('users.email')
            ->whereRaw("LOWER(TRIM(COALESCE(users.role, ''))) = 'employee'")
            ->pluck('users.id')
            ->unique();

        foreach ($userIds as $userId) {
            DB::table('users')->where('id', $userId)->update([
                'temporary_pin' => Crypt::encryptString((string) random_int(100000, 999999)),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('temporary_pin');
        });
    }
};
