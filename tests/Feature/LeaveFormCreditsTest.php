<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeePageController;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveFormCreditsTest extends TestCase
{
    public function test_form_keeps_credits_until_request_is_approved(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 29));
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            foreach ((new LeaveApplication)->getFillable() as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        $user = new User(['first_name' => 'Test', 'last_name' => 'Employee']);
        $user->id = 42;
        $user->setRelation('employee', new Employee([
            'job_type' => 'Non-Teaching', 'employement_date' => '2024-12-01',
        ]));
        $user->setRelation('applicant', null);
        $this->actingAs($user);
        $controller = app(EmployeePageController::class);
        $before = $controller->display_leave()->getData();
        $this->assertGreaterThan(0, $before['formEarnedVacation']);
        $application = LeaveApplication::create([
            'user_id' => 42, 'status' => 'Pending', 'filing_date' => '2026-09-29',
            'leave_type' => 'Annual Leave', 'number_of_working_days' => 3,
            'beginning_vacation' => 0, 'beginning_sick' => 0,
            'earned_vacation' => $before['formEarnedVacation'],
            'earned_sick' => $before['formEarnedSick'],
            'applied_vacation' => 3, 'applied_sick' => 0, 'applied_total' => 3,
            'ending_vacation' => $before['formEarnedVacation'] - 3,
            'ending_sick' => $before['formEarnedSick'],
        ]);
        foreach (['Pending', 'Rejected'] as $status) {
            $application->update(['status' => $status]);
            $data = $controller->display_leave()->getData();
            $this->assertSame($before['formEarnedVacation'], $data['formEarnedVacation']);
            $this->assertSame($before['formEarnedSick'], $data['formEarnedSick']);
            $this->assertSame($before['vacationCardAvailable'], $data['vacationCardAvailable']);
            $this->assertSame(0.0, $data['beginningVacationBalance']);
        }
        $application->update(['status' => 'Approved']);
        $data = $controller->display_leave()->getData();
        $this->assertSame(0.0, $data['formEarnedVacation']);
        $this->assertSame(0.0, $data['formEarnedSick']);
        $this->assertEquals($before['formEarnedVacation'] - 3, $data['beginningVacationBalance']);
        $this->assertEquals($data['vacationCardAvailable'], $data['beginningVacationBalance']);
        $this->assertEquals($data['sickCardAvailable'], $data['beginningSickBalance']);

        // A second request may carry a stale balance from before the first approval.
        LeaveApplication::create([
            'user_id' => 42, 'status' => 'Approved', 'filing_date' => '2026-09-30',
            'leave_type' => 'Annual Leave', 'number_of_working_days' => 2,
            'applied_vacation' => 2, 'applied_sick' => 0, 'applied_total' => 2,
            'ending_vacation' => 3, 'ending_sick' => 5,
        ]);
        $data = $controller->display_leave()->getData();
        $this->assertSame(0.0, $data['vacationCardAvailable']);
        $this->assertSame(0.0, $data['beginningVacationBalance']);
        $this->assertSame(5.0, $data['annualUsed']);
        $this->assertSame(5.0, $data['sickCardAvailable']);
        $this->assertSame(0.0, $data['formEarnedVacation']);

        $application->update(['status' => 'Rejected']);
        $data = $controller->display_leave()->getData();
        $this->assertSame(3.0, $data['vacationCardAvailable']);
        $this->assertSame(3.0, $data['beginningVacationBalance']);

        $this->travelTo(now()->setDate(2026, 10, 15));
        $credits = app(\App\Support\EmployeeLeaveCredits::class)->forMonth($user, now()->startOfMonth());
        $this->assertSame(5.5, $credits['limit']);
        $this->assertSame(3.5, $credits['vacation']);
        $this->assertSame(5.5, $credits['sick']);

        $this->travelTo(now()->setDate(2026, 12, 15));
        $credits = app(\App\Support\EmployeeLeaveCredits::class)->forMonth($user, now()->startOfMonth());
        $this->assertSame(0.5, $credits['vacation']);
        $this->assertSame(0.0, $credits['vacation_used']);
    }
}
