<?php

namespace App\Http\Middleware;

use App\Support\EmployeePresence;
use Closure;
use Illuminate\Http\Request;

class TrackEmployeePresence
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && in_array(strtolower(trim((string) $user->role)), ['employee', 'admin', 'administrator'], true)) {
            $session = (string) ($request->input('tab_session') ?: $request->session()->getId());
            app(EmployeePresence::class)->update((int) $user->id, $session, !$request->routeIs('logout'));
        }

        return $next($request);
    }
}
