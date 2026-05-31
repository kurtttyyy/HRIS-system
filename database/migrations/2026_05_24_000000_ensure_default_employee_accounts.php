<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $now = now();

        foreach ($this->defaultEmployees() as $employee) {
            $userId = $this->ensureUser($employee, $now);

            if (Schema::hasTable('open_positions') && Schema::hasTable('applicants')) {
                $openPositionId = $this->ensureOpenPosition($employee, $now);
                $this->ensureApplicant($employee, $userId, $openPositionId, $now);
            }

            if (Schema::hasTable('employees')) {
                $this->ensureEmployee($employee, $userId, $now);
            }
        }
    }

    public function down(): void
    {
        // Keep demo employee records on rollback to avoid deleting accounts used for testing.
    }

    private function ensureUser(array $employee, mixed $now): int
    {
        $values = [
            'first_name' => $employee['first_name'],
            'middle_name' => $employee['middle_name'],
            'last_name' => $employee['last_name'],
            'role' => 'Employee',
            'job_role' => $employee['position'],
            'status' => 'Approved',
            'account_status' => 'Active',
            'password' => Hash::make($employee['password']),
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('users', 'position')) {
            $values['position'] = $employee['position'];
        }

        if (Schema::hasColumn('users', 'department')) {
            $values['department'] = $employee['department'];
        }

        if (Schema::hasColumn('users', 'department_head')) {
            $values['department_head'] = null;
        }

        $query = DB::table('users')->where('email', $employee['email']);

        if ((clone $query)->exists()) {
            $query->update($values);

            return (int) DB::table('users')->where('email', $employee['email'])->value('id');
        }

        return (int) DB::table('users')->insertGetId($values + [
            'email' => $employee['email'],
            'created_at' => $now,
        ]);
    }

    private function ensureOpenPosition(array $employee, mixed $now): int
    {
        $query = DB::table('open_positions')
            ->where('title', $employee['position'])
            ->where('department', $employee['department'])
            ->whereNull('deleted_at');

        $existingId = (clone $query)->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('open_positions')->insertGetId([
            'title' => $employee['position'],
            'department' => $employee['department'],
            'employment' => 'Full-Time',
            'work_mode' => 'Onsite',
            'job_description' => 'Default employee position for demo account access.',
            'responsibilities' => '-',
            'requirements' => '-',
            'experience_level' => 'Entry Level',
            'location' => 'N/A',
            'skills' => '-',
            'benifits' => '-',
            'job_type' => $employee['job_type'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureApplicant(array $employee, int $userId, int $openPositionId, mixed $now): void
    {
        $values = [
            'user_id' => $userId,
            'open_position_id' => $openPositionId,
            'first_name' => $employee['first_name'],
            'last_name' => $employee['last_name'],
            'phone' => $employee['contact_number'],
            'address' => $employee['address'],
            'field_study' => '-',
            'work_position' => $employee['position'],
            'work_employer' => '-',
            'work_location' => '-',
            'work_duration' => '-',
            'date_hired' => $employee['employement_date'],
            'experience_years' => '0',
            'skills_n_expertise' => '-',
            'application_status' => 'Hired',
            'updated_at' => $now,
        ];

        foreach ([
            'middle_name' => $employee['middle_name'],
            'fresh_graduate' => false,
            'sex' => $employee['sex'],
            'civil_status' => $employee['civil_status'],
            'date_of_birth' => $employee['birthday'],
        ] as $column => $value) {
            if (Schema::hasColumn('applicants', $column)) {
                $values[$column] = $value;
            }
        }

        $query = DB::table('applicants')
            ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($employee['email'])]);

        if ((clone $query)->exists()) {
            $query->update($values);
            return;
        }

        DB::table('applicants')->insert($values + [
            'email' => $employee['email'],
            'created_at' => $now,
        ]);
    }

    private function ensureEmployee(array $employee, int $userId, mixed $now): void
    {
        $values = [
            'employee_id' => $employee['employee_id'],
            'employement_date' => $employee['employement_date'],
            'birthday' => $employee['birthday'],
            'account_number' => $employee['account_number'],
            'sex' => $employee['sex'],
            'civil_status' => $employee['civil_status'],
            'contact_number' => $employee['contact_number'],
            'address' => $employee['address'],
            'department' => $employee['department'],
            'position' => $employee['position'],
            'classification' => $employee['classification'],
            'updated_at' => $now,
        ];

        foreach ([
            'email' => $employee['email'],
            'job_type' => $employee['job_type'],
            'classification_salary' => null,
            'emergency_contact_name' => 'N/A',
            'emergency_contact_relationship' => 'N/A',
            'emergency_contact_number' => 'N/A',
        ] as $column => $value) {
            if (Schema::hasColumn('employees', $column)) {
                $values[$column] = $value;
            }
        }

        $query = DB::table('employees')->where('user_id', $userId);

        if ((clone $query)->exists()) {
            $query->update($values);
            return;
        }

        DB::table('employees')->insert($values + [
            'user_id' => $userId,
            'created_at' => $now,
        ]);
    }

    private function defaultEmployees(): array
    {
        return [
            [
                'email' => 'demo.employee1@example.com',
                'password' => 'Employee12345',
                'first_name' => 'Maria',
                'middle_name' => 'Demo',
                'last_name' => 'Santos',
                'employee_id' => 'EMP-DEMO-001',
                'employement_date' => '2026-01-15',
                'birthday' => '1995-03-12',
                'account_number' => '0000000001',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'contact_number' => '09170000001',
                'address' => 'N/A',
                'department' => 'Human Resources',
                'position' => 'HR Assistant',
                'classification' => 'Probationary',
                'job_type' => 'NT',
            ],
            [
                'email' => 'demo.employee2@example.com',
                'password' => 'Employee12345',
                'first_name' => 'Juan',
                'middle_name' => 'Demo',
                'last_name' => 'Dela Cruz',
                'employee_id' => 'EMP-DEMO-002',
                'employement_date' => '2026-01-15',
                'birthday' => '1993-07-24',
                'account_number' => '0000000002',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'contact_number' => '09170000002',
                'address' => 'N/A',
                'department' => 'Academics',
                'position' => 'Faculty Member',
                'classification' => 'Probationary',
                'job_type' => 'T',
            ],
        ];
    }
};
