<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Interviewer;
use Tests\TestCase;

class GuestInterviewScheduleTest extends TestCase
{
    private function renderSchedule(array $interview = [], string $status = 'Initial Interview'): string
    {
        $applicant = (new Applicant)->forceFill(['application_status' => $status]);
        $applicant->setRelation('interviews', collect([
            (new Interviewer)->forceFill(array_merge([
                'interview_type' => 'Initial Interview', 'date' => '2026-11-04',
                'time' => '14:30:00', 'ended_at' => null,
            ], $interview)),
        ]));
        return view('guest.partials.interview-schedule', compact('applicant'))->render();
    }

    public function test_guest_sees_admin_scheduled_date_and_time(): void
    {
        $html = $this->renderSchedule();
        $this->assertStringContainsString('Wednesday, November 4, 2026', $html);
        $this->assertStringContainsString('2:30 PM', $html);
        $this->assertStringContainsString('Initial Interview', $html);
        $this->assertStringContainsString(config('app.timezone'), $html);
        $this->assertStringContainsString('9:00 AM', $this->renderSchedule(['time' => '09:00:00']));
    }

    public function test_finished_and_previous_stage_schedules_are_hidden(): void
    {
        $this->assertStringNotContainsString('<section', $this->renderSchedule(['ended_at' => '2026-11-04 15:00:00']));
        $this->assertStringNotContainsString('<section', $this->renderSchedule([], 'Final Interview'));
        $this->assertStringNotContainsString('<section', $this->renderSchedule([], 'Rejected'));
    }
}
