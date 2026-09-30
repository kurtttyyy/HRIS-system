<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorPageController;
use App\Models\LeaveApplication;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminSidebarLeaveCountTest extends TestCase
{
    public function test_sidebar_count_tracks_new_and_decided_requests(): void
    {
        Schema::create('employees', fn (Blueprint $table) => $table->id());
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('application_status')->nullable();
            $table->softDeletes();
        });
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        $controller = app(AdministratorPageController::class);
        $count = fn () => $controller->sidebar_summary()->getData(true)['pending_leave_count'];
        $this->assertSame(0, $count());
        $request = LeaveApplication::create(['status' => 'Pending']);
        $this->assertSame(1, $count());
        $request->update(['status' => 'Approved']);
        $this->assertSame(0, $count());
        foreach ([null, '', ' Pending ', 'Rejected', 'Approved'] as $status) {
            LeaveApplication::create(['status' => $status]);
        }
        $this->assertSame(3, $count());
    }
}
