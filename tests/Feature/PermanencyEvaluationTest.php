<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PermanencyEvaluation;
use App\Models\PermanencyEvaluationTemplate;
use App\Models\User;
use App\Support\PermanencyReview;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermanencyEvaluationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\TabSessionAuth::class,
            \App\Http\Middleware\TrackEmployeePresence::class,
            \App\Http\Middleware\RecordUserActivity::class,
        ]);
        $this->travelTo(now()->setDate(2026, 10, 3)->startOfDay());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            foreach (['first_name', 'last_name', 'middle_name', 'role', 'status', 'department', 'department_head', 'position', 'email', 'password'] as $column) $table->string($column)->nullable();
            $table->json('admin_permissions')->nullable();
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id');
            foreach (['classification', 'job_type', 'department', 'position'] as $column) $table->string($column)->nullable();
            $table->date('employement_date')->nullable();
        });
        Schema::create('applicants', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('open_position_id')->nullable();
            $table->string('application_status')->nullable(); $table->date('date_hired')->nullable();
            $table->softDeletes();
        });
        Schema::create('open_positions', function (Blueprint $table) { $table->id(); });
        (require database_path('migrations/2026_10_03_000000_create_permanency_evaluations.php'))->up();
    }

    private function user(array $values = []): User
    {
        return User::withoutEvents(fn () => User::create(array_merge([
            'first_name' => 'Test', 'last_name' => 'Employee', 'role' => 'Employee',
            'status' => 'Approved', 'department' => 'Human Resources',
        ], $values)));
    }

    private function template(): PermanencyEvaluationTemplate
    {
        return PermanencyEvaluationTemplate::create([
            'title' => 'Permanency review', 'notice_days' => 7, 'created_by' => 99,
            'criteria' => [['label' => 'Quality'], ['label' => 'Reliability']],
        ]);
    }

    private function candidate(): User
    {
        $employee = $this->user();
        DB::table('employees')->insert(['user_id' => $employee->id, 'classification' => 'Probationary', 'job_type' => 'Non-Teaching', 'employement_date' => '2026-04-10']);
        return $employee;
    }

    private function payload(int $templateId): array
    {
        return ['template_id' => $templateId, 'ratings' => [1 => 5, 0 => 3], 'recommendation' => 'recommend_permanent', 'comments' => 'Consistent performance and reliable attendance.'];
    }

    public function test_submissions_are_separate_searchable_paginated_and_admin_only(): void
    {
        $template = $this->template();
        $head = $this->user(['department_head' => 'Approved']);
        for ($i = 0; $i < 17; $i++) {
            $employee = $this->user(['first_name' => $i === 0 ? 'UniqueAlice' : 'Worker'.$i, 'last_name' => 'Reviewer']);
            PermanencyEvaluation::create([
                'employee_user_id' => $employee->id, 'head_user_id' => $head->id,
                'template_id' => $template->id, 'due_date' => '2026-10-03',
                'ratings' => [3, 5], 'overall_rating' => 4,
                'recommendation' => $i === 0 ? 'extend_probation' : 'recommend_permanent', 'comments' => 'Review details',
            ]);
        }
        $this->actingAs($head)->get(route('admin.employeeEvaluation.reviews'))->assertForbidden();
        $this->actingAs($this->user(['role' => 'Admin', 'admin_permissions' => ['dashboard']]))
            ->get(route('admin.employeeEvaluation.reviews'))->assertForbidden();
        $this->actingAs($this->user(['role' => 'Admin', 'admin_permissions' => ['employees']]));
        $this->get(route('admin.employeeEvaluation.customize'))->assertOk()->assertDontSee('Review details')->assertSee('17 submissions');
        $this->get(route('admin.employeeEvaluation.reviews'))->assertOk()->assertViewHas('reviews', fn ($reviews) => $reviews->total() === 17 && $reviews->count() === 15);
        $this->get(route('admin.employeeEvaluation.reviews', ['search' => 'UniqueAlice Reviewer', 'recommendation' => 'extend_probation']))
            ->assertOk()->assertSee('UniqueAlice')->assertViewHas('reviews', fn ($reviews) => $reviews->total() === 1);
        $this->get(route('admin.employeeEvaluation.reviews', ['search' => 'Nobody']))->assertOk()->assertSee('No evaluations found');
        $this->get(route('admin.employeeEvaluation.reviews', ['recommendation' => 'recommend_permanent', 'page' => 2]))
            ->assertOk()->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 1 && str_contains($reviews->url(1), 'recommendation=recommend_permanent'));
    }

    public function test_due_dates_and_department_eligibility_use_the_existing_permanency_schedule(): void
    {
        $head = $this->user(['department_head' => 'Approved']);
        $employee = $this->candidate();
        $service = app(PermanencyReview::class);
        $this->assertSame('2026-10-10', $service->dueDate($employee)->toDateString());
        $this->assertTrue($service->eligible($head, $employee, 7));
        $this->assertFalse($service->eligible($head, $employee, 6));
        $head->department = 'Finance';
        $this->assertFalse($service->eligible($head, $employee, 7));
        $head->department = '';
        $this->assertFalse($service->eligible($head, $employee, 7));
        $employee->employee->job_type = 'Teaching';
        $employee->employee->employement_date = '2023-10-10';
        $this->assertSame('2026-10-10', $service->dueDate($employee)->toDateString());
        $employee->employee->classification = 'Permanent';
        $this->assertNull($service->dueDate($employee));
    }

    public function test_only_hr_with_employee_permission_can_publish_a_valid_form(): void
    {
        $payload = ['title' => 'HR review', 'notice_days' => 14, 'criteria' => [['label' => 'Quality']]];
        $this->actingAs($this->user())->post(route('admin.employeeEvaluation.publish'), $payload)->assertForbidden();
        $this->actingAs($this->user(['role' => 'Admin', 'admin_permissions' => ['dashboard']]))->post(route('admin.employeeEvaluation.publish'), $payload)->assertForbidden();
        $this->actingAs($this->user(['role' => 'Admin', 'admin_permissions' => ['employees']]))->post(route('admin.employeeEvaluation.publish'), $payload)->assertRedirect();
        $this->assertSame(1, PermanencyEvaluationTemplate::count());
        $this->post(route('admin.employeeEvaluation.publish'), array_merge($payload, ['criteria' => []]))->assertSessionHasErrors('criteria');
        $this->assertSame(1, PermanencyEvaluationTemplate::count());
    }

    public function test_submission_preserves_scores_and_form_and_prevents_duplicates(): void
    {
        $head = $this->user(['department_head' => 'Approved']);
        $employee = $this->candidate();
        $template = $this->template();
        $this->actingAs($head)->post(route('employee.permanencyEvaluation.submit', $employee), $this->payload($template->id))->assertRedirect(route('employee.permanencyEvaluation'));
        $review = PermanencyEvaluation::firstOrFail();
        $this->assertSame([3, 5], $review->ratings);
        $this->assertEquals(4, $review->overall_rating);
        $this->assertSame($head->id, $review->head_user_id);
        $this->post(route('employee.permanencyEvaluation.submit', $employee), $this->payload($template->id))->assertSessionHasErrors('evaluation');
        $this->assertSame(1, PermanencyEvaluation::count());
        $this->template();
        $this->assertSame($template->id, $review->fresh()->template->id);
    }

    public function test_heads_can_submit_both_evaluation_forms_without_comments(): void
    {
        $head = $this->user(['department_head' => 'Approved']);
        $template = $this->template();
        $this->actingAs($head);
        foreach (['employee.employeeEvaluation.submit', 'employee.permanencyEvaluation.submit'] as $route) {
            foreach (['omitted', 'empty', 'whitespace'] as $variant) {
                $employee = $this->candidate();
                $payload = $this->payload($template->id);
                unset($payload['comments']);
                if ($variant !== 'omitted') $payload['comments'] = $variant === 'empty' ? '' : '   ';
                $this->post(route($route, $employee), $payload)->assertRedirect()->assertSessionHasNoErrors();
                $review = PermanencyEvaluation::where('employee_user_id', $employee->id)->firstOrFail();
                $this->assertSame('', $review->comments);
                $this->assertSame([3, 5], $review->ratings);
            }
        }
    }

    public function test_submission_rejects_other_departments_non_heads_and_early_reviews(): void
    {
        $employee = $this->candidate(); $template = $this->template();
        foreach ([['department_head' => null], ['department_head' => 'Approved', 'department' => 'Finance'], ['department_head' => 'Approved', 'status' => 'Pending']] as $values) {
            $this->actingAs($this->user($values))->post(route('employee.permanencyEvaluation.submit', $employee), $this->payload($template->id))->assertForbidden();
        }
        $this->travelTo(now()->subDay());
        $this->actingAs($this->user(['department_head' => 'Approved']))->post(route('employee.permanencyEvaluation.submit', $employee), $this->payload($template->id))->assertForbidden();
        $this->assertSame(0, PermanencyEvaluation::count());
    }

    public function test_invalid_scores_and_stale_forms_are_rejected(): void
    {
        $employee = $this->candidate(); $template = $this->template();
        $this->actingAs($this->user(['department_head' => 'Approved']));
        $this->post(route('employee.permanencyEvaluation.submit', $employee), array_merge($this->payload($template->id), ['ratings' => [0 => 6, 1 => 3]]))->assertSessionHasErrors('ratings.0');
        $this->template();
        $this->post(route('employee.permanencyEvaluation.submit', $employee), $this->payload($template->id))->assertSessionHasErrors('template_id');
        $this->assertSame(0, PermanencyEvaluation::count());
    }

    public function test_pages_render_empty_eligible_and_submitted_states(): void
    {
        $head = $this->user(['department_head' => 'Approved']);
        $this->actingAs($head)->get(route('employee.permanencyEvaluation'))->assertOk()->assertSee('Evaluation form coming soon');
        $template = $this->template();
        $this->get(route('employee.permanencyEvaluation'))->assertOk()->assertSee('No employees due for evaluation');
        $employee = $this->candidate();
        $this->get(route('employee.permanencyEvaluation'))->assertOk()->assertSee('Awaiting evaluation')->assertSee('ratings[0]', false);
        $this->post(route('employee.permanencyEvaluation.submit', $employee), $this->payload($template->id))->assertRedirect();
        $this->get(route('employee.permanencyEvaluation'))->assertOk()->assertSee('Submitted')->assertSee('Consistent performance');
        $this->actingAs($this->user(['role' => 'Admin']))->get(route('admin.employeeEvaluation.customize'))->assertOk()->assertSee('Evaluation Setup')->assertDontSee('Consistent performance');
        $this->get(route('admin.employeeEvaluation.reviews'))->assertOk()->assertSee('Consistent performance');
    }

    public function test_saved_team_evaluation_remains_visible_after_submission_day_and_template_changes(): void
    {
        $head = $this->user(['department_head' => 'Approved']);
        $employee = $this->user();
        $template = $this->template();
        $this->actingAs($head)->post(route('employee.employeeEvaluation.submit', $employee), $this->payload($template->id))->assertRedirect();
        $this->travelTo(now()->addDay());
        PermanencyEvaluationTemplate::create([
            'title' => 'New questions', 'notice_days' => 7, 'created_by' => 99,
            'criteria' => [['label' => 'New criterion']],
        ]);
        $response = $this->get(route('employee.employeeEvaluation'));
        $response->assertOk()->assertSee('Submitted Oct 3, 2026')->assertSee('Overall rating: 4/5')
            ->assertSee('Recommend permanent appointment')->assertSee('Consistent performance and reliable attendance.')
            ->assertSee('Quality: 3 - Satisfactory')->assertSee('Reliability: 5 - Excellent')
            ->assertDontSee('Submit evaluation to HR')->assertDontSee('name="recommendation"', false);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/value="3"[^>]*checked[^>]*disabled/s', $html);
        $this->assertMatchesRegularExpression('/value="5"[^>]*checked[^>]*disabled/s', $html);
        PermanencyEvaluation::firstOrFail()->update(['comments' => '']);
        $this->get(route('employee.employeeEvaluation'))->assertOk()->assertSee('No comments provided.');
    }

    public function test_published_questions_appear_for_the_heads_team_and_can_be_submitted(): void
    {
        $head = $this->user(['department_head' => 'Approved']);
        $employee = $this->user(['first_name' => 'TeamMember']);
        $other = $this->user(['first_name' => 'OtherDepartment', 'department' => 'Finance']);
        $admin = $this->user(['role' => 'Admin', 'admin_permissions' => ['employees']]);
        $this->actingAs($admin)->post(route('admin.employeeEvaluation.publish'), [
            'title' => 'Custom team evaluation', 'notice_days' => 7,
            'criteria' => [['label' => 'How accurate is the work?'], ['label' => 'How reliable is the employee?']],
        ])->assertRedirect();
        $template = PermanencyEvaluationTemplate::latest('id')->firstOrFail();
        $this->actingAs($head)->get(route('employee.employeeEvaluation'))
            ->assertOk()->assertSee('TeamMember')->assertDontSee('OtherDepartment')
            ->assertSee('How accurate is the work?')->assertSee('How reliable is the employee?')
            ->assertSee('ratings[1]', false);
        $this->post(route('employee.employeeEvaluation.submit', $other), $this->payload($template->id))->assertForbidden();
        $this->post(route('employee.employeeEvaluation.submit', $employee), $this->payload($template->id))
            ->assertRedirect(route('employee.employeeEvaluation'));
        $this->assertSame([3, 5], PermanencyEvaluation::firstOrFail()->ratings);
        $this->get(route('employee.employeeEvaluation'))->assertOk()->assertSee('Submitted today');
        $this->post(route('employee.employeeEvaluation.submit', $employee), $this->payload($template->id))->assertSessionHasErrors('evaluation');
    }
}
