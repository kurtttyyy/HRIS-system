<?php

namespace Database\Seeders;

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

        foreach ($this->defaultEmployeeAccounts() as $account) {
            $employee = User::updateOrCreate([
                'email' => $account['email'],
            ], [
                'first_name' => $account['first_name'],
                'last_name' => $account['last_name'],
                'middle_name' => $account['middle_name'],
                'role' => 'Employee',
                'job_role' => $account['job_role'],
                'position' => $account['position'],
                'department' => $account['department'],
                'department_head' => null,
                'status' => 'Approved',
                'account_status' => 'Active',
                'password' => Hash::make($account['password']),
            ]);

            if (Schema::hasTable('employees')) {
                DB::table('employees')->updateOrInsert([
                    'user_id' => $employee->id,
                ], [
                    'employee_id' => $account['employee_id'],
                    'employement_date' => now()->toDateString(),
                    'birthday' => now()->subYears(18)->toDateString(),
                    'account_number' => 'N/A',
                    'sex' => 'Unspecified',
                    'civil_status' => 'Single',
                    'contact_number' => 'N/A',
                    'address' => 'N/A',
                    'department' => $account['department'],
                    'position' => $account['position'],
                    'classification' => 'Probationary',
                    'deleted_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function defaultEmployeeAccounts(): array
    {
        return [
            [
                'email' => env('DEFAULT_EMPLOYEE_EMAIL', 'employee@example.com'),
                'password' => env('DEFAULT_EMPLOYEE_PASSWORD', 'Employee12345'),
                'first_name' => env('DEFAULT_EMPLOYEE_FIRST_NAME', 'Default'),
                'middle_name' => env('DEFAULT_EMPLOYEE_MIDDLE_NAME', 'Account'),
                'last_name' => env('DEFAULT_EMPLOYEE_LAST_NAME', 'Employee'),
                'job_role' => env('DEFAULT_EMPLOYEE_JOB_ROLE', 'Employee'),
                'position' => env('DEFAULT_EMPLOYEE_POSITION', 'Employee'),
                'department' => env('DEFAULT_EMPLOYEE_DEPARTMENT', 'General'),
                'employee_id' => env('DEFAULT_EMPLOYEE_ID', 'EMP-DEFAULT'),
            ],
            [
                'email' => 'maria.santos@example.com',
                'password' => 'Maria12345',
                'first_name' => 'Maria',
                'middle_name' => 'Reyes',
                'last_name' => 'Santos',
                'job_role' => 'HR Staff',
                'position' => 'HR Staff',
                'department' => 'Human Resources',
                'employee_id' => 'EMP-MARIA',
            ],
            [
                'email' => 'juan.delacruz@example.com',
                'password' => 'Juan12345',
                'first_name' => 'Juan',
                'middle_name' => 'Dela',
                'last_name' => 'Cruz',
                'job_role' => 'Office Staff',
                'position' => 'Office Staff',
                'department' => 'General',
                'employee_id' => 'EMP-JUAN',
            ],
        ];
    }
}
