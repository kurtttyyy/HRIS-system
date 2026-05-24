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
                'department_head' => null,
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

    public function down(): void
    {
        // Do not delete real admin accounts on rollback.
    }

    private function defaultAccounts(): array
    {
        return [
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
            ],
        ];
    }
};
