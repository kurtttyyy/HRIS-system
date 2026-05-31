<?php

namespace Database\Seeders;

use App\Models\OpenPosition;
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

        foreach ($this->defaultOpenPositions() as $position) {
            OpenPosition::updateOrCreate([
                'title' => $position['title'],
                'department' => $position['department'],
            ], $position);
        }

        foreach ($this->defaultEmployeeAccounts() as $employee) {
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

    private function defaultOpenPositions(): array
    {
        $startDate = now()->startOfDay();
        $endDate = now()->addDays(30)->endOfDay();

        return [
            [
                'title' => 'Administrative Staff',
                'department' => 'Human Resources',
                'employment' => 'Full-Time',
                'work_mode' => 'Onsite',
                'job_description' => 'Support daily HR operations, employee records, and office coordination.',
                'responsibilities' => 'Maintain employee files, assist with HR requests, prepare reports, and coordinate administrative tasks.',
                'requirements' => 'Strong communication skills, basic computer literacy, and attention to detail.',
                'experience_level' => 'Entry Level',
                'location' => 'Main Campus',
                'skills' => 'MS Office, Records Management, Communication',
                'benifits' => 'Government benefits, Paid leave',
                'job_type' => 'NT',
                'one' => $startDate,
                'two' => $endDate,
            ],
            [
                'title' => 'Faculty Teacher',
                'department' => 'Academics',
                'employment' => 'Full-Time',
                'work_mode' => 'Onsite',
                'job_description' => 'Deliver classroom instruction, prepare learning materials, and support student development.',
                'responsibilities' => 'Prepare lesson plans, evaluate student performance, maintain class records, and participate in academic activities.',
                'requirements' => 'Relevant degree, teaching ability, and commitment to student-centered instruction.',
                'experience_level' => 'Entry Level',
                'location' => 'Main Campus',
                'skills' => 'Teaching, Lesson Planning, Classroom Management',
                'benifits' => 'Government benefits, Paid leave',
                'job_type' => 'T',
                'one' => $startDate,
                'two' => $endDate,
            ],
            [
                'title' => 'Maintenance Staff',
                'department' => 'General Services',
                'employment' => 'Full-Time',
                'work_mode' => 'Onsite',
                'job_description' => 'Assist with facility upkeep, repairs, and general campus maintenance.',
                'responsibilities' => 'Perform routine maintenance, respond to facility requests, and keep work areas clean and safe.',
                'requirements' => 'Basic maintenance skills, reliability, and willingness to work on-site.',
                'experience_level' => 'Entry Level',
                'location' => 'Main Campus',
                'skills' => 'Facility Maintenance, Safety, Teamwork',
                'benifits' => 'Government benefits, Paid leave',
                'job_type' => 'NT',
                'one' => $startDate,
                'two' => $endDate,
            ],
        ];
    }

    private function defaultEmployeeAccounts(): array
    {
        return [
            [
                'email' => 'employee.one@example.com',
                'password' => 'Employee12345',
                'first_name' => 'Employee',
                'middle_name' => 'Default',
                'last_name' => 'One',
                'department' => 'Human Resources',
                'position' => 'Administrative Staff',
            ],
            [
                'email' => 'employee.two@example.com',
                'password' => 'Employee12345',
                'first_name' => 'Employee',
                'middle_name' => 'Default',
                'last_name' => 'Two',
                'department' => 'Academics',
                'position' => 'Faculty Teacher',
            ],
        ];
    }
}
