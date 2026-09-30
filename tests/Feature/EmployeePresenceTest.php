<?php

namespace Tests\Feature;

use App\Support\EmployeePresence;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmployeePresenceTest extends TestCase
{
    public function test_employees_are_offline_until_they_have_a_live_session(): void
    {
        Cache::flush();
        $presence = app(EmployeePresence::class);
        $this->assertFalse($presence->isOnline(42));
        $presence->update(42, 'tab-one', true);
        $this->assertTrue($presence->isOnline(42));
        $presence->update(42, 'tab-one', false);
        $this->assertFalse($presence->isOnline(42));
    }

    public function test_closed_sessions_expire_and_heartbeats_keep_sessions_online(): void
    {
        Cache::flush();
        $presence = app(EmployeePresence::class);
        $presence->update(42, 'tab-one', true);
        $this->travel(60)->seconds();
        $presence->update(42, 'tab-one', true);
        $this->travel(60)->seconds();
        $this->assertTrue($presence->isOnline(42));
        $this->travel(31)->seconds();
        $this->assertFalse($presence->isOnline(42));
    }

    public function test_logging_out_one_session_preserves_another_session(): void
    {
        Cache::flush();
        $presence = app(EmployeePresence::class);
        $presence->update(42, 'tab-one', true);
        $presence->update(42, 'tab-two', true);
        $presence->update(42, 'tab-one', false);
        $this->assertTrue($presence->isOnline(42));
        $presence->update(42, 'tab-two', false);
        $this->assertFalse($presence->isOnline(42));
    }
}
