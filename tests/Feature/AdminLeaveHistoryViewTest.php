<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use Tests\TestCase;

class AdminLeaveHistoryViewTest extends TestCase
{
    public function test_approved_request_can_be_viewed_without_decision_controls(): void
    {
        $request = new LeaveApplication([
            'employee_name' => 'History Employee', 'employee_id' => 'EMP-42',
            'position' => 'Instructor', 'leave_type' => 'Sick Leave',
            'filing_date' => '2026-09-29', 'inclusive_dates' => 'Sep 29 - Sep 30, 2026',
            'number_of_working_days' => 2, 'status' => 'Approved',
            'medical_receipt_path' => 'leave-medical-receipts/example.pdf',
            'medical_receipt_name' => 'example.pdf', 'medical_receipt_mime' => 'application/pdf',
            'beginning_sick' => 5, 'applied_sick' => 2, 'ending_sick' => 3,
        ]);
        $request->id = 42;
        $render = fn ($readOnly) => view('Admin.partials.leaveRequestModal', [
            'request' => $request, 'readOnly' => $readOnly, 'selectedMonthValue' => '2026-09',
        ])->render();
        $html = $render(true);
        foreach (['leave-review-modal-42', 'History Employee', 'EMP-42', 'Instructor',
            'Sep 29 - Sep 30, 2026', 'Leave Credits', 'example.pdf', 'Approved request'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('data-leave-decision-form', $html);
        $this->assertStringContainsString('data-leave-review-close', $html);
        $pendingHtml = $render(false);
        $this->assertStringContainsString('data-leave-decision-form', $pendingHtml);
        $this->assertStringContainsString('value="Approved"', $pendingHtml);
        $this->assertStringContainsString('value="Rejected"', $pendingHtml);
    }
}
