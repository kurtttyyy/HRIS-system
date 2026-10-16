<?php

namespace Tests\Feature;

use App\Http\Controllers\AdministratorPageController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminApplicantNotificationsTest extends TestCase
{
    public function test_submitted_applications_show_names_position_and_submission_date(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->string('role');
            $table->string('status');
        });
        Schema::create('open_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
        });
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('open_position_id')->nullable();
            foreach (['first_name', 'middle_name', 'last_name', 'name_extension'] as $field) {
                $table->string($field)->nullable();
            }
            $table->softDeletes();
            $table->timestamps();
        });
        DB::table('open_positions')->insert(['id' => 1, 'title' => 'Teacher']);
        DB::table('applicants')->insert([
            ['id' => 1, 'open_position_id' => 1, 'first_name' => 'Ana', 'middle_name' => 'Maria', 'last_name' => 'Santos', 'name_extension' => 'Jr.', 'created_at' => '2026-10-01 09:00:00', 'updated_at' => '2026-10-05 09:00:00', 'deleted_at' => null],
            ['id' => 2, 'open_position_id' => 99, 'first_name' => 'Ben', 'middle_name' => null, 'last_name' => 'Cruz', 'name_extension' => null, 'created_at' => '2026-10-02 09:00:00', 'updated_at' => '2026-10-02 09:00:00', 'deleted_at' => null],
            ['id' => 3, 'open_position_id' => 1, 'first_name' => 'Deleted', 'middle_name' => null, 'last_name' => 'Applicant', 'name_extension' => null, 'created_at' => '2026-10-03 09:00:00', 'updated_at' => '2026-10-03 09:00:00', 'deleted_at' => '2026-10-04 09:00:00'],
            ['id' => 4, 'open_position_id' => null, 'first_name' => 'Imported', 'middle_name' => null, 'last_name' => 'Employee', 'name_extension' => null, 'created_at' => '2026-10-04 09:00:00', 'updated_at' => '2026-10-04 09:00:00', 'deleted_at' => null],
        ]);
        $method = new \ReflectionMethod(AdministratorPageController::class, 'buildAdminNotifications');
        [$items, $stats] = $method->invoke(app(AdministratorPageController::class), [], [], 3, []);
        $submitted = $items->where('badge', 'Submitted')->values();
        $this->assertCount(2, $submitted);
        $this->assertSame('Ben Cruz submitted an application form.', $submitted[0]['message']);
        $this->assertSame('Ana Maria Santos Jr. submitted an application form for Teacher.', $submitted[1]['message']);
        $this->assertSame('2026-10-01 09:00:00', $submitted[1]['date']->format('Y-m-d H:i:s'));
        $this->assertSame('application-submitted-1', $submitted[1]['id']);
        $this->assertSame(3, $stats['hiring']);
    }
}
