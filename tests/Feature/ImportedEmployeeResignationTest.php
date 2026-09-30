<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Resignation;
use App\Support\ImportedEmployeeResignation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportedEmployeeResignationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('account_status');
            $table->text('temporary_pin')->nullable();
            $table->timestamps();
        });
        Schema::create('resignations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('effective_date');
            $table->string('reason');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_reimport_disables_active_account_and_does_not_duplicate_resignation(): void
    {
        $user = User::withoutEvents(fn () => User::create([
            'account_status' => 'Active', 'temporary_pin' => '123456',
        ]));
        $service = app(ImportedEmployeeResignation::class);
        $service->record($user, '2025-06-30', []);
        $service->record($user, '2025-06-30', []);
        $this->assertSame('Inactive', $user->fresh()->account_status);
        $this->assertNull($user->fresh()->temporary_pin);
        $this->assertSame(1, Resignation::count());
        $this->assertSame('Approved', Resignation::first()->status);
        $this->assertSame('2025-06-30', Resignation::first()->effective_date->toDateString());
    }

    public function test_imported_inactive_employee_cannot_reactivate_with_old_pin(): void
    {
        $user = User::withoutEvents(fn () => User::create([
            'account_status' => 'Inactive', 'temporary_pin' => '123456',
        ]));
        app(ImportedEmployeeResignation::class)->record($user, '2024-01-01', []);
        $this->assertSame('Inactive', $user->fresh()->account_status);
        $this->assertNull($user->fresh()->temporary_pin);
    }
}
