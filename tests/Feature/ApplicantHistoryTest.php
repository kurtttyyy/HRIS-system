<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorPageController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApplicantHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('open_position_id')->nullable();
            foreach (['first_name', 'middle_name', 'last_name', 'name_extension', 'phone', 'bachelor_degree', 'master_degree', 'doctoral_degree', 'field_study', 'application_status'] as $field) {
                $table->string($field)->nullable();
            }
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('open_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
        });
        Schema::create('applicant_degrees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->string('degree_name');
            $table->integer('sort_order')->default(0);
        });
        DB::table('open_positions')->insert(['id' => 1, 'title' => 'Teacher', 'deleted_at' => now()]);
        foreach ([1 => ' Rejected ', 2 => 'rejected', 3 => 'Under Review', 4 => 'Hired'] as $id => $status) {
            DB::table('applicants')->insert([
                'id' => $id, 'open_position_id' => 1, 'first_name' => 'Ana', 'middle_name' => 'Maria',
                'last_name' => 'Santos', 'phone' => '09123456789', 'bachelor_degree' => 'BS Education',
                'application_status' => $status, 'created_at' => now(), 'updated_at' => now(),
                'deleted_at' => $id === 2 ? now() : null,
            ]);
        }
        DB::table('applicant_degrees')->insert(['applicant_id' => 1, 'degree_name' => 'Master of Education']);
    }

    private function history(string $search = ''): array
    {
        return app(AdministratorPageController::class)
            ->display_applicant_history(Request::create('/system/applicant/history', 'GET', ['search' => $search]))
            ->getData();
    }

    public function test_history_includes_all_rejected_records_and_archived_positions(): void
    {
        $applicants = $this->history()['applicants'];
        $this->assertSame(2, $applicants->total());
        $this->assertSame([2, 1], $applicants->pluck('id')->all());
        $this->assertSame('Teacher', $applicants->first()->position->title);
        $this->assertSame('Master of Education', $applicants->last()->degrees->first()->degree_name);
        $this->assertSame('BS Education', $applicants->last()->bachelor_degree);
        $this->assertSame('09123456789', $applicants->first()->phone);
    }

    public function test_search_matches_full_names_phone_archived_position_and_degrees(): void
    {
        foreach (['Ana Maria Santos' => 2, '09123456789' => 2, 'Teacher' => 2, 'Master of Education' => 1, 'BS Education' => 2, 'No match' => 0] as $search => $count) {
            $this->assertSame($count, $this->history($search)['applicants']->total(), $search);
        }
    }

    public function test_page_renders_required_columns_and_hiring_navigation(): void
    {
        $html = app(AdministratorPageController::class)
            ->display_applicant_history(Request::create('/system/applicant/history'))->render();
        foreach (['Full name', 'Phone number', 'Position applied', 'Degree', 'Ana Maria Santos', '09123456789', 'Teacher', 'Master of Education', 'BS Education', 'Applicant History'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_degree_column_hides_level_labels_and_empty_placeholders(): void
    {
        foreach (['Elementary', 'Secondary', 'College', 'N/A'] as $name) {
            DB::table('applicant_degrees')->insert(['applicant_id' => 1, 'degree_name' => $name]);
        }
        DB::table('applicants')->where('id', 1)->update(['master_degree' => 'College', 'doctoral_degree' => 'N/A']);
        $html = app(AdministratorPageController::class)
            ->display_applicant_history(Request::create('/system/applicant/history'))->render();
        foreach (['Elementary', 'Secondary', 'College', 'N/A'] as $name) {
            $this->assertStringNotContainsString('<div class="leading-6">'.$name.'</div>', $html);
        }
        $this->assertStringContainsString('<div class="leading-6">BS Education</div>', $html);
        $this->assertStringContainsString('<div class="leading-6">Master of Education</div>', $html);
    }

    public function test_year_and_month_filters_use_submission_dates_and_combine_with_search(): void
    {
        DB::table('applicants')->where('id', 1)->update(['created_at' => '2025-01-31 23:59:59']);
        DB::table('applicants')->where('id', 2)->update(['created_at' => '2026-01-01 00:00:00']);
        $cases = [
            [['year' => 2025], [1]],
            [['year' => 2026, 'month' => 1], [2]],
            [['month' => 1], [2, 1]],
            [['year' => 2025, 'month' => 2], []],
            [['year' => 2025, 'month' => 1, 'search' => 'Master of Education'], [1]],
            [['year' => 2026, 'search' => 'Master of Education'], []],
        ];
        foreach ($cases as [$filters, $ids]) {
            $data = app(AdministratorPageController::class)
                ->display_applicant_history(Request::create('/system/applicant/history', 'GET', $filters))->getData();
            $this->assertSame($ids, $data['applicants']->pluck('id')->all());
        }
        $html = app(AdministratorPageController::class)
            ->display_applicant_history(Request::create('/system/applicant/history', 'GET', ['year' => 2025, 'month' => 1]))->render();
        $this->assertStringContainsString('value="2025" selected', $html);
        $this->assertStringContainsString('value="1" selected', $html);
    }

    public function test_new_rejection_appears_automatically_and_history_is_paginated(): void
    {
        DB::table('applicants')->where('id', 3)->update(['application_status' => 'Rejected']);
        $this->assertSame(3, $this->history()['applicants']->total());
        for ($id = 5; $id <= 25; $id++) {
            DB::table('applicants')->insert(['id' => $id, 'first_name' => 'Applicant '.$id, 'application_status' => 'Rejected', 'updated_at' => now()]);
        }
        $applicants = $this->history()['applicants'];
        $this->assertSame(24, $applicants->total());
        $this->assertCount(20, $applicants->items());
        $this->assertSame(2, $applicants->lastPage());
    }
}
