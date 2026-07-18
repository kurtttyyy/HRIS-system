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

        foreach ($this->accounts() as $account) {
            $values = [
                'first_name' => $account['first_name'],
                'middle_name' => $account['middle_name'],
                'last_name' => $account['last_name'],
                'role' => 'Employee',
                'job_role' => $account['job_role'],
                'position' => $account['position'],
                'department' => 'Information Technology',
                'department_head' => $account['department_head'],
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

            $this->ensureEmployeeProfile($account, $now);
            $this->ensureHiredApplicant($account, $now);
        }
    }

    private function ensureEmployeeProfile(array $account, $now): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        $userId = DB::table('users')->where('email', $account['email'])->value('id');

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
            'department' => 'Information Technology',
            'position' => $account['position'],
            'classification' => 'Probationary',
            'deleted_at' => null,
            'updated_at' => $now,
            'created_at' => $now,
        ]);
    }

    private function ensureHiredApplicant(array $account, $now): void
    {
        if (!Schema::hasTable('applicants') || !Schema::hasTable('open_positions')) {
            return;
        }

        $userId = DB::table('users')->where('email', $account['email'])->value('id');

        if (!$userId) {
            return;
        }

        $payload = [
            'user_id' => $userId,
            'open_position_id' => $this->resolveOpenPositionId($account, $now),
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

    private function resolveOpenPositionId(array $account, $now): int
    {
        $openPositionId = DB::table('open_positions')
            ->where('title', $account['position'])
            ->where('department', 'Information Technology')
            ->value('id');

        if ($openPositionId) {
            return (int) $openPositionId;
        }

        return (int) DB::table('open_positions')->insertGetId([
            'title' => $account['position'],
            'department' => 'Information Technology',
            'employment' => 'Full-Time',
            'work_mode' => 'Onsite',
            'job_description' => 'Default position for the seeded department head team.',
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
        // Keep seeded default accounts on rollback.
    }

    private function accounts(): array
    {
        return array_values(array_filter([
            [
                'email' => 'ana.reyes@example.com',
                'password' => 'Ana12345',
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'last_name' => 'Reyes',
                'job_role' => 'IT Support Specialist',
                'position' => 'IT Support Specialist',
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
                'employee_id' => 'EMP-CARLOS',
                'department_head' => 'Approved',
            ],
        ], static fn (array $account): bool => false));
    }
};
