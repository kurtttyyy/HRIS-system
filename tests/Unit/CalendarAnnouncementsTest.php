<?php

namespace Tests\Unit;

use App\Support\CalendarAnnouncements;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CalendarAnnouncementsTest extends TestCase
{
    public function test_calendar_announcements_use_saved_events_and_hide_removed_holidays(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-10 12:00:00'));
        Storage::fake('local');
        Cache::shouldReceive('remember')->andReturn([
            ['date' => '2026-10-12', 'localName' => 'Hidden holiday'],
            ['date' => '2026-10-13', 'localName' => 'Visible holiday'],
        ]);
        Storage::disk('local')->put('calendar_holiday_config.json', json_encode([
            'custom_events' => ['2026-10-10' => ['Today meeting'], '2026-10-09' => ['Past event'], '2026-11-01' => ['Next month event'], '2026-09-30' => ['Previous month event'], '2026-10-15' => ['Staff meeting'], '2026-02-30' => ['Invalid date']],
            'custom_holidays' => ['2026-10-16' => ['School holiday']],
            'recurring_events' => ['01-02' => ['Next year event']],
            'recurring_exams' => ['10-20' => ['Final exams']],
            'hidden_official_holidays' => ['2026-10-12' => ['Hidden holiday']],
            'hidden_special_events' => ['2026-10-31' => ['Halloween']],
        ]));
        $items = CalendarAnnouncements::upcoming();
        $titles = $items->pluck('title')->all();
        foreach (['Past event', 'Today meeting', 'Staff meeting', 'School holiday', 'Final exams', 'Visible holiday'] as $title) $this->assertContains($title, $titles);
        foreach (['Next year event', 'Next month event', 'Previous month event', 'Invalid date', 'Hidden holiday', 'Halloween'] as $title) $this->assertNotContains($title, $titles);
        $this->assertSame('Past event', $items->first()['title']);
        $this->assertTrue($items->every(fn ($item) => str_starts_with($item['date'], '2026-10-')));
        $this->travelBack();
    }
}
