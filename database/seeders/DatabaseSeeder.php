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

<<<<<<< HEAD
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
=======
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
                'department_head' => $account['department_head'] ?? null,
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

            $this->ensureHiredApplicant($employee->id, $account);
        }
    }

    private function ensureHiredApplicant(int $userId, array $account): void
    {
        if (!Schema::hasTable('applicants') || !Schema::hasTable('open_positions')) {
            return;
        }

        $now = now();
        $openPositionId = $this->resolveDefaultOpenPositionId($account);
        $payload = [
            'user_id' => $userId,
            'open_position_id' => $openPositionId,
            'first_name' => $account['first_name'],
            'last_name' => $account['last_name'],
            'email' => $account['email'],
            'field_study' => '-',
            'work_position' => $account['position'],
            'work_employer' => '-',
            'work_location' => '-',
            'work_duration' => '-',
            'date_hired' => $now->toDateString(),
            'experience_years' => '0',
            'skills_n_expertise' => '-',
            'application_status' => 'Hired',
            'deleted_at' => null,
            'updated_at' => $now,
            'created_at' => $now,
        ];

        foreach ([
            'middle_name' => $account['middle_name'],
            'education_attainment' => '-',
            'university_name' => '-',
            'university_address' => '-',
            'year_complete' => '-',
            'fresh_graduate' => false,
        ] as $column => $value) {
            if (Schema::hasColumn('applicants', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('applicants')->updateOrInsert([
            'email' => $account['email'],
        ], $payload);
    }

    private function resolveDefaultOpenPositionId(array $account): int
    {
        $openPositionId = DB::table('open_positions')
            ->where('title', $account['position'])
            ->where('department', $account['department'])
            ->value('id');

        if ($openPositionId) {
            return (int) $openPositionId;
        }

        return (int) DB::table('open_positions')->insertGetId([
            'title' => $account['position'],
            'department' => $account['department'],
            'employment' => 'Full-Time',
            'work_mode' => 'Onsite',
            'job_description' => 'Default position for seeded employee accounts.',
            'responsibilities' => '-',
            'requirements' => '-',
            'experience_level' => 'Entry Level',
            'location' => 'N/A',
            'skills' => '-',
            'benifits' => '-',
            'job_type' => 'NT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
                'department_head' => null,
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
                'department_head' => null,
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
                'department_head' => null,
            ],
            [
                'email' => 'ana.reyes@example.com',
                'password' => 'Ana12345',
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'last_name' => 'Reyes',
                'job_role' => 'IT Support Specialist',
                'position' => 'IT Support Specialist',
                'department' => 'Information Technology',
                'employee_id' => 'EMP-ANA',
                'department_head' => null,
            ],
            [
                'email' => 'miguel.torres@example.com',
                'password' => 'Miguel12345',
                'first_name' => 'Miguel',
                'middle_name' => 'Santos',
                'last_name' => 'Torres',
                'job_role' => 'Systems Technician',
                'position' => 'Systems Technician',
                'department' => 'Information Technology',
                'employee_id' => 'EMP-MIGUEL',
                'department_head' => null,
            ],
            [
                'email' => 'carlos.rivera@example.com',
                'password' => 'Carlos12345',
                'first_name' => 'Carlos',
                'middle_name' => 'Garcia',
                'last_name' => 'Rivera',
                'job_role' => 'Head of IT Staff',
                'position' => 'Head of IT Staff',
                'department' => 'Information Technology',
                'employee_id' => 'EMP-CARLOS',
                'department_head' => 'Approved',
>>>>>>> 958d01319837153d6a8ebc1a74af493a6035ad0f
            ],
        ];
    }
}
