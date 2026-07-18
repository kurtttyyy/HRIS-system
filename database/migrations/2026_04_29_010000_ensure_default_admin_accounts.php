<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->defaultAccounts() as $account) {
            $values = [
                'first_name' => $account['first_name'],
                'middle_name' => $account['middle_name'],
                'last_name' => $account['last_name'],
                'role' => $account['role'],
                'job_role' => $account['job_role'],
                'position' => $account['position'],
                'department' => $account['department'],
                'department_head' => $account['department_head'] ?? null,
                'status' => 'Approved',
                'account_status' => 'Active',
                'password' => Hash::make($account['password']),
                'updated_at' => $now,
            ];

            $query = DB::table('users')->where('email', $account['email']);

            if ((clone $query)->exists()) {
                $query->update($values);
            } else {
                DB::table('users')->insert($values + [
                    'email' => $account['email'],
                    'created_at' => $now,
                ]);
            }

            if ($account['role'] === 'Employee') {
                $this->ensureEmployeeProfile($account['email'], $account, $now);
                $this->ensureHiredApplicant($account['email'], $account, $now);
            }
        }
    }

    private function ensureEmployeeProfile(string $email, array $account, $now): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        $userId = DB::table('users')->where('email', $email)->value('id');

        if (!$userId) {
            return;
        }

        DB::table('employees')->updateOrInsert([
            'user_id' => $userId,
        ], [
            'employee_id' => $account['employee_id'],
            'employement_date' => $now->toDateString(),
            'birthday' => $now->copy()->subYears(18)->toDateString(),
            'account_number' => 'N/A',
            'sex' => 'Unspecified',
            'civil_status' => 'Single',
            'contact_number' => 'N/A',
            'address' => 'N/A',
            'department' => $account['department'],
            'position' => $account['position'],
            'classification' => 'Probationary',
            'deleted_at' => null,
            'updated_at' => $now,
            'created_at' => $now,
        ]);
    }

    private function ensureHiredApplicant(string $email, array $account, $now): void
    {
        if (!Schema::hasTable('applicants') || !Schema::hasTable('open_positions')) {
            return;
        }

        $userId = DB::table('users')->where('email', $email)->value('id');

        if (!$userId) {
            return;
        }

        $openPositionId = $this->resolveDefaultOpenPositionId($account, $now);
        $payload = [
            'user_id' => $userId,
            'open_position_id' => $openPositionId,
            'first_name' => $account['first_name'],
            'last_name' => $account['last_name'],
            'email' => $email,
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
            'email' => $email,
        ], $payload);
    }

    private function resolveDefaultOpenPositionId(array $account, $now): int
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
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Do not delete real admin accounts on rollback.
    }

    private function defaultAccounts(): array
    {
        return array_values(array_filter([
            [
                'email' => 'demo.admin@example.com',
                'password' => 'Demo12345',
                'first_name' => 'Demo',
                'middle_name' => 'Account',
                'last_name' => 'Admin',
                'role' => 'Admin',
                'job_role' => 'Administrator',
                'position' => 'Administrator',
                'department' => 'Human Resources',
                'employee_id' => null,
                'department_head' => null,
            ],
            [
                'email' => 'kurtrobin@gmail.com',
                'password' => 'Kurt12345',
                'first_name' => 'Kurt',
                'middle_name' => 'Admin',
                'last_name' => 'Robin',
                'role' => 'Admin',
                'job_role' => 'Administrator',
                'position' => 'Administrator',
                'department' => 'Human Resources',
                'employee_id' => null,
                'department_head' => null,
            ],
            [
                'email' => 'employee@example.com',
                'password' => 'Employee12345',
                'first_name' => 'Default',
                'middle_name' => 'Account',
                'last_name' => 'Employee',
                'role' => 'Employee',
                'job_role' => 'Employee',
                'position' => 'Employee',
                'department' => 'General',
                'employee_id' => 'EMP-DEFAULT',
                'department_head' => null,
            ],
            [
                'email' => 'maria.santos@example.com',
                'password' => 'Maria12345',
                'first_name' => 'Maria',
                'middle_name' => 'Reyes',
                'last_name' => 'Santos',
                'role' => 'Employee',
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
                'role' => 'Employee',
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
                'role' => 'Employee',
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
                'role' => 'Employee',
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
                'role' => 'Employee',
                'job_role' => 'Head of IT Staff',
                'position' => 'Head of IT Staff',
                'department' => 'Information Technology',
                'employee_id' => 'EMP-CARLOS',
                'department_head' => 'Approved',
            ],
        ], static fn (array $account): bool => false));
    }
};
