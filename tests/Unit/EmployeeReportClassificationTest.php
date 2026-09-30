<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Employee;
use App\Models\Applicant;
use App\Support\EmployeeReportClassification as Classification;
use PHPUnit\Framework\TestCase;

class EmployeeReportClassificationTest extends TestCase
{
    private function employee(array $profile = [], array $account = []): User
    {
        $user = new User($account);
        $user->setRelation('employee', new Employee($profile));
        $applicant = new Applicant();
        $applicant->setRelation('position', null);
        $user->setRelation('applicant', $applicant);
        return $user;
    }

    public function test_imported_gender_codes_and_missing_values(): void
    {
        foreach (['M' => 'male', ' Male ' => 'male', 'FM' => 'female', 'Fm' => 'female', 'F' => 'female', 'Female' => 'female', 'Unspecified' => null, '' => null] as $value => $expected) {
            $this->assertSame($expected, Classification::gender($this->employee(['sex' => $value])));
        }
    }

    public function test_reports_use_the_same_staffing_rules_as_the_directory(): void
    {
        $employees = collect([
            $this->employee(['position' => 'Supervisor']),
            $this->employee(['position' => 'Focal Person']),
            $this->employee(['position' => 'Asst.Registrar']),
            $this->employee(['position' => 'HR Assistant'], ['job_role' => 'Department Head']),
            $this->employee(['position' => 'Instructor', 'job_type' => 'Teaching']),
        ]);
        $directory = \App\Support\DepartmentStaffingSummary::forDepartment($employees, 'Unassigned');
        $report = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertSame(3, $directory['heads']);
        $this->assertSame(1, $directory['staff']);
        $this->assertSame(1, $directory['instructors_ft']);
        $this->assertSame($directory['heads'], $report->sum('heads'));
        $this->assertSame(5, $report->sum('total'));
    }
}
