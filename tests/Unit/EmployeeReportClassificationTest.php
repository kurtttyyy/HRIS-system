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

    public function test_excel_class_codes_determine_instructor_counts(): void
    {
        $employees = collect([
            $this->employee(['position' => 'Lecturer', 'class_code' => 'T/FT', 'classification' => 'Part-Time']),
            $this->employee(['position' => 'Employee', 'class_code' => ' t / pt ', 'classification' => 'Full-Time']),
            $this->employee(['position' => 'Instructor', 'class_code' => 'NT/FT']),
            $this->employee(['position' => 'Instructor', 'class_code' => 'NT/PT']),
            $this->employee(['position' => 'Dean', 'class_code' => 'T/FT', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['position' => 'Coordinator', 'class_code' => 'T/PT', 'classification' => 'Coordinator I']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forDepartment($employees, 'College');
        $this->assertSame(2, $summary['instructors_ft']);
        $this->assertSame(2, $summary['instructors_pt']);
        $this->assertSame(0, $summary['heads']);
        $this->assertSame(0, $summary['coordinator']);
        $this->assertSame(2, $summary['unclassified']);
        $this->assertSame(6, $summary['total']);
    }

    public function test_reports_use_the_same_staffing_rules_as_the_directory(): void
    {
        $employees = collect([
            $this->employee(['position' => 'Supervisor', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['position' => 'Focal Person', 'classification' => 'Asst.Admin/Acad Head III']),
            $this->employee(['position' => 'Asst.Registrar']),
            $this->employee(['position' => 'HR Assistant'], ['job_role' => 'Department Head']),
            $this->employee(['position' => 'Instructor', 'job_type' => 'Teaching']),
        ]);
        $directory = \App\Support\DepartmentStaffingSummary::forDepartment($employees, 'Unassigned');
        $report = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertSame(2, $directory['heads']);
        $this->assertSame(0, $directory['staff']);
        $this->assertSame(2, $directory['unclassified']);
        $this->assertSame(1, $directory['instructors_ft']);
        $this->assertSame($directory['heads'], $report->sum('heads'));
        $this->assertSame(5, $report->sum('total'));
    }
    public function test_head_ranks_accept_levels_and_reject_position_titles(): void
    {
        foreach (['Admin/Acad Head III', 'Admin/Acad Head II', 'Admin/Acad Head I', 'Asst.Admin/Acad Head III', 'Assistant Admin/Acad Head 2', 'Asst. Admin/Acad Head 1', 'Acad Head', 'admin', ' ADMIN/ACAD HEAD II '] as $rank) {
            $this->assertTrue(\App\Support\DepartmentStaffingSummary::isHeadRank($rank), $rank);
        }
        foreach (['', 'Dean', 'President', 'Coordinator I', 'School Clerk II', 'Administrative Assistant', 'Admin/Acad Head IV', 'Admin/Acad Head 10'] as $rank) {
            $this->assertFalse(\App\Support\DepartmentStaffingSummary::isHeadRank($rank), $rank);
        }
    }

    public function test_coordinators_are_counted_by_rank_only(): void
    {
        $employees = collect();
        foreach (['Coordinator I', 'Coordinator II', 'Coordinator III', 'Coordinator 1', 'Coordinator 2', ' coordinator  3 '] as $rank) {
            $employees->push($this->employee(['position' => 'Instructor', 'classification' => $rank]));
        }
        foreach (['', 'Coordinator', 'Coordinator IV', 'Coordinator 10', 'School Clerk II'] as $rank) {
            $employees->push($this->employee(['position' => 'Coordinator', 'classification' => $rank]));
        }
        $summary = \App\Support\DepartmentStaffingSummary::forDepartment($employees, 'College');
        $this->assertSame(6, $summary['coordinator']);
        $this->assertSame(1, $summary['staff']);
        $this->assertSame(4, $summary['unclassified']);
        $this->assertSame(0, $summary['instructors_ft']);
        $this->assertSame(11, $summary['total']);
    }

    public function test_assistant_heads_are_counted_once_alongside_other_heads(): void
    {
        $employees = collect([
            $this->employee(['position' => 'Dean', 'classification' => 'Admin/Acad Head III']),
            $this->employee(['position' => 'Coordinator', 'classification' => 'Assistant Admin/Acad Head 1']),
            $this->employee(['position' => 'Coordinator', 'classification' => 'Coordinator I']),
            $this->employee(['position' => 'Dean']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forDepartment($employees, 'College');
        $this->assertSame(2, $summary['heads']);
        $this->assertSame(1, $summary['coordinator']);
        $this->assertSame(0, $summary['staff']);
        $this->assertSame(1, $summary['unclassified']);
        $this->assertSame(4, $summary['total']);
    }
    public function test_department_groups_merge_case_and_spacing_variants(): void
    {
        $employees = collect([
            $this->employee(['department' => 'LAW-Office', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['department' => 'Law-Office', 'classification' => 'School Clerk I']),
            $this->employee(['department' => ' law - office ', 'classification' => 'UJAM']),
            $this->employee(['department' => 'LAW', 'classification' => 'UJAM']),
            $this->employee(['department' => 'CABA-OFFICE', 'classification' => 'UJAM']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(3, $summary);
        $this->assertSame(3, $summary['LAW-OFFICE']['total']);
        $this->assertSame(1, $summary['LAW-OFFICE']['heads']);
        $this->assertSame(2, $summary['LAW-OFFICE']['staff']);
        $this->assertSame(5, $summary->sum('total'));
    }

    public function test_treasury_and_accounting_share_one_summary_group(): void
    {
        $employees = collect([
            $this->employee(['department' => "Treasurer's Office", 'classification' => 'Admin/Acad Head I']),
            $this->employee(['department' => 'Accounting', 'classification' => 'School Clerk I']),
            $this->employee(['department' => 'Treasury', 'classification' => 'Coordinator I']),
            $this->employee(['department' => 'TREASURY & ACCOUNTING', 'classification' => 'UJAM']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(1, $summary);
        $this->assertSame(4, $summary['TREASURY & ACCOUNTING']['total']);
        $this->assertSame(1, $summary['TREASURY & ACCOUNTING']['heads']);
        $this->assertSame(1, $summary['TREASURY & ACCOUNTING']['coordinator']);
        $this->assertSame(2, $summary['TREASURY & ACCOUNTING']['staff']);
    }

    public function test_bec_departments_share_one_summary_group(): void
    {
        $employees = collect();
        foreach (['BEC', 'bec-elem', 'BEC-SHS', 'BEC-JHS', 'BEC-STEC', 'BEC-STE', ' BEC - OFFICE '] as $department) {
            $employees->push($this->employee(['department' => $department, 'classification' => 'UJAM']));
        }
        $employees->push($this->employee(['department' => 'CABA', 'classification' => 'UJAM']));
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(2, $summary);
        $this->assertSame(7, $summary['BEC']['total']);
        $this->assertSame(7, $summary['BEC']['staff']);
        $this->assertSame(8, $summary->sum('total'));
    }

    public function test_caba_and_its_office_share_one_summary_group(): void
    {
        $employees = collect([
            $this->employee(['department' => 'CABA', 'position' => 'Instructor']),
            $this->employee(['department' => 'caba-office', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['department' => ' CABA - OFFICE ', 'classification' => 'UJAM']),
            $this->employee(['department' => 'CABA Office', 'classification' => 'Coordinator I']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(1, $summary);
        $this->assertSame(4, $summary['CABA']['total']);
        foreach (['heads', 'coordinator', 'staff', 'instructors_ft'] as $category) {
            $this->assertSame(1, $summary['CABA'][$category]);
        }
    }

    public function test_cit_and_its_office_share_the_coit_summary_group(): void
    {
        $employees = collect([
            $this->employee(['department' => 'CIT', 'position' => 'Instructor']),
            $this->employee(['department' => 'cit-office', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['department' => ' CIT - OFFICE ', 'classification' => 'UJAM']),
            $this->employee(['department' => 'CIT Office', 'classification' => 'Coordinator I']),
            $this->employee(['department' => 'COIT', 'position' => 'Instructor']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(1, $summary);
        $this->assertSame(5, $summary['COIT']['total']);
        $this->assertSame(2, $summary['COIT']['instructors_ft']);
        foreach (['heads', 'coordinator', 'staff'] as $category) {
            $this->assertSame(1, $summary['COIT'][$category]);
        }
    }

    public function test_coc_lab_and_office_share_the_coc_summary_group(): void
    {
        $employees = collect([
            $this->employee(['department' => 'COC', 'position' => 'Instructor']),
            $this->employee(['department' => 'coc-lab', 'classification' => 'UJAM']),
            $this->employee(['department' => ' COC - OFFICE ', 'classification' => 'School Clerk I']),
            $this->employee(['department' => 'COC LAB', 'classification' => 'UJAM']),
            $this->employee(['department' => 'COC Office', 'classification' => 'Coordinator I']),
            $this->employee(['department' => 'COIT', 'position' => 'Instructor']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(2, $summary);
        $this->assertSame(5, $summary['COC']['total']);
        $this->assertSame(3, $summary['COC']['staff']);
        $this->assertSame(1, $summary['COC']['coordinator']);
        $this->assertSame(1, $summary['COC']['instructors_ft']);
        $this->assertSame(6, $summary->sum('total'));
    }

    public function test_coed_and_its_office_share_one_summary_group(): void
    {
        $employees = collect([
            $this->employee(['department' => 'COED', 'position' => 'Instructor']),
            $this->employee(['department' => 'coed-office', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['department' => ' COED - office ', 'classification' => 'UJAM']),
            $this->employee(['department' => 'COED Office', 'classification' => 'Coordinator I']),
            $this->employee(['department' => 'COC', 'position' => 'Instructor']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(2, $summary);
        $this->assertSame(4, $summary['COED']['total']);
        foreach (['heads', 'coordinator', 'staff', 'instructors_ft'] as $category) {
            $this->assertSame(1, $summary['COED'][$category]);
        }
        $this->assertSame(5, $summary->sum('total'));
    }

    public function test_coge_and_its_office_share_one_summary_group(): void
    {
        $employees = collect([
            $this->employee(['department' => 'COGE', 'position' => 'Instructor']),
            $this->employee(['department' => 'coge-office', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['department' => ' COGE - OFFICE ', 'classification' => 'UJAM']),
            $this->employee(['department' => 'COGE Office', 'classification' => 'Coordinator I']),
            $this->employee(['department' => 'COED', 'position' => 'Instructor']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(2, $summary);
        $this->assertSame(4, $summary['COGE']['total']);
        foreach (['heads', 'coordinator', 'staff', 'instructors_ft'] as $category) {
            $this->assertSame(1, $summary['COGE'][$category]);
        }
        $this->assertSame(5, $summary->sum('total'));
    }

    public function test_cohm_laboratory_and_office_share_one_summary_group(): void
    {
        $employees = collect([
            $this->employee(['department' => 'COHM', 'position' => 'Instructor']),
            $this->employee(['department' => 'cohm-office', 'classification' => 'Admin/Acad Head I']),
            $this->employee(['department' => ' COHM - CULINARY  LABORATORY ', 'classification' => 'UJAM']),
            $this->employee(['department' => 'COHM Office', 'classification' => 'Coordinator I']),
            $this->employee(['department' => 'COHM CULINARY LABORATORY', 'classification' => 'School Clerk I']),
            $this->employee(['department' => 'COGE', 'position' => 'Instructor']),
        ]);
        $summary = \App\Support\DepartmentStaffingSummary::forEmployees($employees);
        $this->assertCount(2, $summary);
        $this->assertSame(5, $summary['COHM']['total']);
        $this->assertSame(2, $summary['COHM']['staff']);
        foreach (['heads', 'coordinator', 'instructors_ft'] as $category) {
            $this->assertSame(1, $summary['COHM'][$category]);
        }
        $this->assertSame(6, $summary->sum('total'));
    }

    public function test_staff_are_counted_only_by_supported_ranks(): void
    {
        $employees = collect();
        foreach (['School Clerk I', 'School Clerk II', 'School Clerk III', 'School Clerck 1', 'School Clerk 2', 'School Clerk 3', 'Contractual-Other Ranks', ' UJAM ', 'school  clerk  ii'] as $rank) {
            $employees->push($this->employee(['position' => 'Instructor', 'classification' => $rank]));
        }
        foreach (['', 'School Clerk IV', 'School Clerk 10', 'Staff', 'Other Ranks'] as $rank) {
            $employees->push($this->employee(['position' => 'School Clerk', 'classification' => $rank]));
        }
        $summary = \App\Support\DepartmentStaffingSummary::forDepartment($employees, 'Office');
        $this->assertSame(9, $summary['staff']);
        $this->assertSame(5, $summary['unclassified']);
        $this->assertSame(0, $summary['instructors_ft']);
        $this->assertSame(14, $summary['total']);
    }
}
