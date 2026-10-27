<?php

namespace Tests\Feature;

use App\Models\{User, Employee, Applicant};
use App\Support\ReportPeriod;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportPeriodTest extends TestCase
{
    public function test_month_filter_uses_employment_date_before_account_creation(): void
    {
        $user = (new User)->forceFill(['created_at' => '2026-10-27']);
        $user->setRelation('employee', (new Employee)->forceFill(['employement_date' => '2022-01-31']));
        $user->setRelation('applicant', null);
        $this->assertTrue(ReportPeriod::employeeJoinedIn($user, Carbon::parse('2022-01-01'), Carbon::parse('2022-01-31')->endOfDay()));
        $this->assertFalse(ReportPeriod::employeeJoinedIn($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')->endOfDay()));
        $user->setRelation('employee', null);
        $user->setRelation('applicant', (new Applicant)->forceFill(['date_hired' => '2022-01-01']));
        $this->assertTrue(ReportPeriod::employeeJoinedIn($user, Carbon::parse('2022-01-01'), Carbon::parse('2022-01-31')->endOfDay()));
        $user->setRelation('applicant', null);
        $this->assertTrue(ReportPeriod::employeeJoinedIn($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')->endOfDay()));
    }
}
