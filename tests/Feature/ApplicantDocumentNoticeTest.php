<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorStoreController;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ApplicantDocumentNoticeTest extends TestCase
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
        (require database_path('migrations/2026_10_06_000001_add_document_notice_to_applicants.php'))->up();
        Auth::setUser((new User)->forceFill(['id' => 1, 'role' => 'admin']));
    }

    private function applicant(): Applicant
    {
        return Applicant::create(['application_status' => 'Passing Document']);
    }

    private function saveNotice(Applicant $applicant, array $data)
    {
        return app(AdministratorStoreController::class)->save_applicant_document_notice(
            Request::create('/system/applicant/'.$applicant->id.'/document-notice', 'POST', $data), $applicant
        );
    }

    private function guestHtml(Applicant $applicant): string
    {
        $applicant->setRelation('position', null);
        $applicant->setRelation('degrees', collect());
        $applicant->setRelation('documents', collect());
        return view('guest.partials.application-status-board', ['applicants' => collect([$applicant])])->render();
    }

    public function test_published_notice_is_saved_and_shown_safely_to_guest_then_resolved(): void
    {
        $applicant = $this->applicant();
        $this->saveNotice($applicant, ['missing_documents' => "Transcript\nGovernment ID", 'document_notice' => '<script>alert(1)</script> Submit to HR.']);
        $applicant->refresh();
        $this->assertNotNull($applicant->document_notice_at);
        $html = $this->guestHtml($applicant);
        $this->assertStringContainsString('Action required: missing documents', $html);
        $this->assertStringContainsString('<li>Transcript</li>', $html);
        $this->assertStringContainsString('<li>Government ID</li>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->saveNotice($applicant, ['resolve' => true]);
        $applicant->unsetRelations();
        $applicant->refresh();
        $this->assertNull($applicant->missing_documents);
        $this->assertNull($applicant->document_notice_at);
        $this->assertStringNotContainsString('Action required: missing documents', $this->guestHtml($applicant));
        $this->assertSame('Passing Document', $applicant->application_status);
    }

    public function test_notice_requires_missing_document_names(): void
    {
        $this->expectException(ValidationException::class);
        $this->saveNotice($this->applicant(), ['missing_documents' => '  ']);
    }

    public function test_employee_cannot_publish_notice(): void
    {
        Auth::setUser((new User)->forceFill(['id' => 2, 'role' => 'employee']));
        $this->expectException(HttpException::class);
        $this->saveNotice($this->applicant(), ['missing_documents' => 'Transcript']);
    }

    public function test_notice_cannot_be_published_for_rejected_applicant(): void
    {
        $applicant = $this->applicant();
        $applicant->update(['application_status' => 'Rejected']);
        $this->expectException(HttpException::class);
        $this->saveNotice($applicant, ['missing_documents' => 'Transcript']);
    }

    public function test_active_notice_blocks_completion_and_hiring_on_server(): void
    {
        $applicant = $this->applicant();
        $this->saveNotice($applicant, ['missing_documents' => 'Transcript']);
        foreach (['Completed', 'Hired'] as $status) {
            try {
                app(AdministratorStoreController::class)->update_application_status(Request::create('/system/update/application/status', 'POST', [
                    'reviewId' => $applicant->id, 'status' => $status, 'date_hired' => '2026-10-06',
                ]));
                $this->fail('Active document notice must prevent '.$status);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('status', $exception->errors());
            }
        }
        $this->assertSame('Passing Document', $applicant->fresh()->application_status);
    }
}
