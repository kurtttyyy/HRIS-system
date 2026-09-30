<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorPageController;
use App\Models\LeaveApplication;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLeaveSnapshotTest extends TestCase
{
    public function test_new_submissions_change_the_snapshot_even_outside_the_selected_month(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->date('filing_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        $controller = app(AdministratorPageController::class);
        $request = Request::create('/employee/leave/snapshot', 'GET', ['month' => '2026-09']);
        $before = $controller->leave_management_snapshot($request)->getData(true);
        foreach (['2026-09-29', '2026-10-01'] as $index => $date) {
            LeaveApplication::create(['filing_date' => $date, 'status' => 'Pending']);
            $after = $controller->leave_management_snapshot($request)->getData(true);
            $this->assertNotSame($before['token'], $after['token']);
            $this->assertSame($index + 1, $after['pending']);
            $before = $after;
        }
    }
}
