<?php

namespace Tests\Feature;

use App\Models\Applicant;
use Tests\TestCase;

class GuestReportingDateTest extends TestCase
{
    private function renderDate(string $status, ?string $date): string
    {
        $applicant = (new Applicant)->forceFill(['application_status' => $status, 'date_hired' => $date]);
        return view('guest.partials.reporting-date', compact('applicant'))->render();
    }

    public function test_hired_applicant_sees_hr_reporting_date_and_changes(): void
    {
        $this->assertStringContainsString('Report on Monday, November 2, 2026', $this->renderDate('Hired', '2026-11-02'));
        $this->assertStringContainsString('Report on Wednesday, November 4, 2026', $this->renderDate('Hired', '2026-11-04'));
    }

    public function test_missing_date_has_clear_message_and_other_stages_do_not_show_card(): void
    {
        $this->assertStringContainsString('reporting date has not been set yet', $this->renderDate('Hired', null));
        $this->assertStringNotContainsString('<section', $this->renderDate('Initial Interview', '2026-11-02'));
    }
}
