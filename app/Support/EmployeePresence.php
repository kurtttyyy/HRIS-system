<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class EmployeePresence
{
    public function update(int $userId, string $session, bool $online): void
    {
        $key = 'employee-presence:'.$userId;
        Cache::lock($key.':lock', 5)->block(3, function () use ($key, $session, $online) {
            $sessions = array_filter(Cache::get($key, []), fn ($expires) => $expires > now()->timestamp);
            if ($online) {
                $sessions[$session] = now()->timestamp + 90;
            } else {
                unset($sessions[$session]);
            }
            Cache::put($key, $sessions, 90);
        });
    }

    public function isOnline(int $userId): bool
    {
        return collect(Cache::get('employee-presence:'.$userId, []))
            ->contains(fn ($expires) => $expires > now()->timestamp);
    }
}
