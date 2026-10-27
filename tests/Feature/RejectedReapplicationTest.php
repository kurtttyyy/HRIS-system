<?php

namespace Tests\Feature;

use App\Http\Controllers\GuestPageController;
use App\Models\Applicant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RejectedReapplicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->unsignedInteger('open_position_id');
            $table->string('application_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_newly_rejected_application_does_not_block_same_email_and_position(): void
    {
        Applicant::create(['email' => 'applicant@example.com', 'open_position_id' => 1, 'application_status' => ' Rejected ']);
        $this->assertFalse(Applicant::where('email', 'applicant@example.com')->where('open_position_id', 1)->blocksReapplication()->exists());
        $method = new \ReflectionMethod(GuestPageController::class, 'getBlockedPositionIds');
        $this->assertEmpty($method->invoke(app(GuestPageController::class), ' APPLICANT@example.com ')->all());
        Applicant::create(['email' => 'applicant@example.com', 'open_position_id' => 1, 'application_status' => 'pending']);
        $this->assertTrue(Applicant::where('email', 'applicant@example.com')->where('open_position_id', 1)->blocksReapplication()->exists());
        $this->assertEquals([1], $method->invoke(app(GuestPageController::class), 'applicant@example.com')->values()->all());
        $this->assertSame(2, Applicant::count());
    }

    public function test_non_rejected_stages_still_block_duplicates(): void
    {
        foreach (['pending', 'Under Review', 'Initial Interview', 'Hired', null] as $status) {
            $record = Applicant::create(['email' => 'person@example.com', 'open_position_id' => 2, 'application_status' => $status]);
            $this->assertTrue(Applicant::whereKey($record->id)->blocksReapplication()->exists());
        }
    }
}
