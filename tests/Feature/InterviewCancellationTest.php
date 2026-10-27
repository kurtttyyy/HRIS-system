<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorStoreController;
use App\Models\Applicant;
use App\Models\Interviewer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InterviewCancellationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('application_status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('interviewers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->string('interview_type');
            $table->date('date');
            $table->string('time');
            $table->string('duration');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_cancel_preserves_stage_and_completed_initial_interview(): void
    {
        foreach (['Initial Interview', 'Demo Teaching', 'Final Interview'] as $stage) {
            $applicant = Applicant::create(['application_status' => $stage]);
            $initial = Interviewer::create([
                'applicant_id' => $applicant->id, 'interview_type' => 'Initial Interview',
                'date' => now()->toDateString(), 'time' => '08:00', 'duration' => '5 minutes', 'ended_at' => now(),
            ]);
            $schedule = Interviewer::create([
                'applicant_id' => $applicant->id, 'interview_type' => $stage,
                'date' => now()->addDays(2)->toDateString(), 'time' => '13:00', 'duration' => '5 minutes',
            ]);
            app(AdministratorStoreController::class)->destroy_interview($applicant->id);
            $this->assertTrue($schedule->fresh()->trashed());
            $this->assertFalse($initial->fresh()->trashed());
            $this->assertSame($stage, $applicant->fresh()->application_status);
            app(AdministratorStoreController::class)->destroy_interview($applicant->id);
            $this->assertFalse($initial->fresh()->trashed());
        }
    }
}
