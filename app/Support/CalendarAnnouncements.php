<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CalendarAnnouncements
{
    public static function upcoming(): \Illuminate\Support\Collection
    {
        $config = Storage::disk('local')->exists('calendar_holiday_config.json')
            ? json_decode(Storage::disk('local')->get('calendar_holiday_config.json'), true) : [];
        $config = is_array($config) ? $config : [];
        $today = now()->startOfMonth()->startOfDay();
        $until = $today->copy()->endOfMonth()->startOfDay();
        $entries = collect();
        $push = function ($date, $title, $type) use ($entries, $today, $until) {
            if (!is_string($title) || trim($title) === '') return;
            try {
                $day = Carbon::createFromFormat('!Y-m-d', $date);
                if (!$day || $day->format('Y-m-d') !== $date || !$day->betweenIncluded($today, $until)) return;
                $entries->push(['date' => $date, 'title' => trim($title), 'type' => $type]);
            } catch (\Throwable $e) {
                // Ignore malformed calendar dates.
            }
        };
        foreach (['custom_events' => 'Event', 'custom_holidays' => 'Holiday'] as $key => $type) {
            foreach (($config[$key] ?? []) as $date => $titles) {
                foreach (is_array($titles) ? $titles : [] as $title) $push($date, $title, $type);
            }
        }
        for ($day = $today->copy(); $day->lte($until); $day->addDay()) {
            foreach (['recurring_events' => 'Event', 'recurring_holidays' => 'Holiday', 'recurring_exams' => 'Exam'] as $key => $type) {
                $titles = $config[$key][$day->format('m-d')] ?? [];
                foreach (is_array($titles) ? $titles : [] as $title) $push($day->toDateString(), $title, $type);
            }
        }
        foreach (range($today->year, $until->year) as $year) {
            $specials = ['02-14' => "Valentine's Day", '03-08' => "International Women's Day", '04-22' => 'Earth Day', '10-31' => 'Halloween', '11-01' => "All Saints' Day", '11-02' => "All Souls' Day", '12-24' => 'Christmas Eve', '12-31' => "New Year's Eve"];
            $chineseNewYears = [2024 => '02-10', 2025 => '01-29', 2026 => '02-17', 2027 => '02-06', 2028 => '01-26', 2029 => '02-13', 2030 => '02-03', 2031 => '01-23', 2032 => '02-11', 2033 => '01-31', 2034 => '02-19', 2035 => '02-08'];
            if (isset($chineseNewYears[$year])) $specials[$chineseNewYears[$year]] = 'Chinese New Year';
            foreach ($specials as $monthDay => $title) {
                $date = $year.'-'.$monthDay;
                $hidden = $config['hidden_special_events'][$date] ?? [];
                if (!in_array($title, is_array($hidden) ? $hidden : [], true)) $push($date, $title, 'Special');
            }
        }
        // Match the official holiday country used by the admin calendar.
        foreach (range($today->year, $until->year) as $year) {
            try {
                $holidays = Cache::remember('calendar_announcements_us_'.$year, 21600, function () use ($year) {
                    $response = Http::connectTimeout(2)->timeout(3)->get('https://date.nager.at/api/v3/PublicHolidays/'.$year.'/US');
                    return $response->successful() ? $response->json() : [];
                });
                foreach (is_array($holidays) ? $holidays : [] as $holiday) {
                    $date = $holiday['date'] ?? '';
                    $title = $holiday['localName'] ?? $holiday['name'] ?? '';
                    $hidden = $config['hidden_official_holidays'][$date] ?? [];
                    if (!in_array($title, is_array($hidden) ? $hidden : [], true)) $push($date, $title, 'Holiday');
                }
            } catch (\Throwable $e) {
                // Saved admin events remain available when the holiday provider is offline.
            }
        }
        return $entries->unique(fn ($entry) => $entry['date'].'|'.$entry['title'])
            ->sortBy(fn ($entry) => $entry['date'].'|'.$entry['title'])->values();
    }
}
