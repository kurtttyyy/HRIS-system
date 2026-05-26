<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => env('DEFAULT_ADMIN_EMAIL', 'kurtrobin20031118@gmail.com'),
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

        foreach ($this->defaultEmployees() as $employee) {
            User::updateOrCreate([
                'email' => $employee['email'],
            ], [
                'first_name' => $employee['first_name'],
                'last_name' => $employee['last_name'],
                'middle_name' => $employee['middle_name'],
                'role' => 'Employee',
                'job_role' => $employee['position'],
                'position' => $employee['position'],
                'department' => $employee['department'],
                'department_head' => null,
                'status' => 'Approved',
                'account_status' => 'Active',
                'password' => Hash::make($employee['password']),
            ]);
        }
    }

    private function defaultEmployees(): array
    {
        return [
            [
                'email' => env('DEMO_EMPLOYEE_ONE_EMAIL', 'demo.employee1@example.com'),
                'password' => env('DEMO_EMPLOYEE_ONE_PASSWORD', 'Employee12345'),
                'first_name' => env('DEMO_EMPLOYEE_ONE_FIRST_NAME', 'Maria'),
                'middle_name' => env('DEMO_EMPLOYEE_ONE_MIDDLE_NAME', 'Demo'),
                'last_name' => env('DEMO_EMPLOYEE_ONE_LAST_NAME', 'Santos'),
                'position' => env('DEMO_EMPLOYEE_ONE_POSITION', 'HR Assistant'),
                'department' => env('DEMO_EMPLOYEE_ONE_DEPARTMENT', 'Human Resources'),
            ],
            [
                'email' => env('DEMO_EMPLOYEE_TWO_EMAIL', 'demo.employee2@example.com'),
                'password' => env('DEMO_EMPLOYEE_TWO_PASSWORD', 'Employee12345'),
                'first_name' => env('DEMO_EMPLOYEE_TWO_FIRST_NAME', 'Juan'),
                'middle_name' => env('DEMO_EMPLOYEE_TWO_MIDDLE_NAME', 'Demo'),
                'last_name' => env('DEMO_EMPLOYEE_TWO_LAST_NAME', 'Dela Cruz'),
                'position' => env('DEMO_EMPLOYEE_TWO_POSITION', 'Faculty Member'),
                'department' => env('DEMO_EMPLOYEE_TWO_DEPARTMENT', 'Academics'),
            ],
        ];
    }
}
