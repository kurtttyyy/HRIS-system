<?php

namespace Database\Seeders;

use App\Models\OpenPosition;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => env('DEFAULT_ADMIN_EMAIL', 'kurtrobin@gmail.com'),
        ], [
            'first_name' => env('DEFAULT_ADMIN_FIRST_NAME', 'Kurt'),
            'last_name' => env('DEFAULT_ADMIN_LAST_NAME', 'Robin'),
            'middle_name' => env('DEFAULT_ADMIN_MIDDLE_NAME', 'Admin'),
            'role' => 'Admin',
            'job_role' => 'Administrator',
            'position' => 'Administrator',
            'department' => 'Human Resources',
            'department_head' => null,
            'status' => 'Approved',
            'account_status' => 'Active',
            'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD', 'Kurt12345')),
        ]);

        User::updateOrCreate([
            'email' => env('DEMO_ADMIN_EMAIL', 'demo.admin@example.com'),
        ], [
            'first_name' => env('DEMO_ADMIN_FIRST_NAME', 'Demo'),
            'last_name' => env('DEMO_ADMIN_LAST_NAME', 'Admin'),
            'middle_name' => env('DEMO_ADMIN_MIDDLE_NAME', 'Account'),
            'role' => 'Admin',
            'job_role' => 'Administrator',
            'position' => 'Administrator',
            'department' => 'Human Resources',
            'department_head' => null,
            'status' => 'Approved',
            'account_status' => 'Active',
            'password' => Hash::make(env('DEMO_ADMIN_PASSWORD', 'Demo12345')),
        ]);
    }
}
