<?php

namespace Tests\Feature;

use App\Models\OpenPosition;
use Tests\TestCase;

class TeachingApplicationDocumentsTest extends TestCase
{
    public function test_teaching_position_shows_additional_uploads_and_review_entries(): void
    {
        $html = $this->applicationHtml('Teaching');

        $this->assertStringContainsString('id="research_publication"', $html);
        $this->assertStringContainsString('id="training_certification"', $html);
        $this->assertStringContainsString('name="documents[9][type]" value="Research Publication"', $html);
        $this->assertStringContainsString('name="documents[10][type]" value="Training Certification"', $html);
        $this->assertStringContainsString('id="review-research-publication"', $html);
        $this->assertStringContainsString('id="review-training-certification"', $html);
    }

    public function test_non_teaching_position_keeps_existing_uploads_only(): void
    {
        $html = $this->applicationHtml('Non-Teaching');

        $this->assertStringNotContainsString('id="research_publication"', $html);
        $this->assertStringNotContainsString('id="training_certification"', $html);
        $this->assertStringNotContainsString('id="review-research-publication"', $html);
        $this->assertStringContainsString('id="resume"', $html);
    }

    private function applicationHtml(string $type): string
    {
        $position = (new OpenPosition)->forceFill([
            'id' => 123, 'title' => 'Sample Position', 'job_type' => $type,
        ]);

        return view('guest.applicationNonTeachingSteps', [
            'openPosition' => $position,
            'errors' => new \Illuminate\Support\ViewErrorBag,
        ])->render();
    }
}
