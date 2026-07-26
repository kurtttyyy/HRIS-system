<?php

namespace App\Http;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TabSessionAuth
{
    public function handle(Request $request, Closure $next)
    {
        $tabSession = trim((string) ($request->input('tab_session') ?? $request->query('tab_session') ?? ''));
        $tabSessionScope = $this->resolveTabSessionScope($request);

        $isFreshAuthEntry = $tabSession === '' && in_array((string) optional($request->route())->getName(), ['login_display', 'register'], true);

        if ($tabSession === '' && !$isFreshAuthEntry) {
            $tabSession = $this->resolveStoredTabSession($request, $tabSessionScope);
        }

        if ($tabSession !== '') {
            $request->merge(['tab_session' => $tabSession]);
            if (!$request->query('tab_session')) {
                $request->query->set('tab_session', $tabSession);
            }
            $request->attributes->set('tab_session', $tabSession);
            $request->session()->put('last_tab_session_'.$tabSessionScope, $tabSession);
            $request->session()->put('last_tab_session', $tabSession);

            $tabAuthMap = $request->session()->get($this->tabAuthMapKey($tabSessionScope), []);
            if (!is_array($tabAuthMap) || empty($tabAuthMap)) {
                $tabAuthMap = $request->session()->get('tab_auth_users', []);
            }
            $userId = (int) ($tabAuthMap[$tabSession] ?? 0);

            if ($userId > 0) {
                $user = User::query()->find($userId);
                if ($user && $this->userMatchesScope($user, $tabSessionScope)) {
                    Auth::setUser($user);
                } else {
                    unset($tabAuthMap[$tabSession]);
                    $request->session()->put($this->tabAuthMapKey($tabSessionScope), $tabAuthMap);
                    $legacyTabAuthMap = $request->session()->get('tab_auth_users', []);
                    if (is_array($legacyTabAuthMap)) {
                        unset($legacyTabAuthMap[$tabSession]);
                        $request->session()->put('tab_auth_users', $legacyTabAuthMap);
                    }
                }
            }
        }

        return $next($request);
    }

    protected function resolveTabSessionScope(Request $request): string
    {
        $path = ltrim($request->path(), '/');
        $routeName = (string) optional($request->route())->getName();

        if (
            str_starts_with($path, 'system/')
            || str_starts_with($path, 'admin/')
            || str_starts_with($routeName, 'admin.')
        ) {
            return 'admin';
        }

        if (
            str_starts_with($path, 'employee/')
            || str_starts_with($routeName, 'employee.')
        ) {
            return 'employee';
        }

        return 'web';
    }

    protected function resolveStoredTabSession(Request $request, string $tabSessionScope): string
    {
        $session = $request->session();

        $candidates = array_filter([
            $session->get('last_tab_session_'.$tabSessionScope, ''),
            $tabSessionScope === 'web' ? $session->get('last_tab_session', '') : '',
        ], static fn ($value) => trim((string) $value) !== '');

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    protected function tabAuthMapKey(string $tabSessionScope): string
    {
        return 'tab_auth_users_'.$tabSessionScope;
    }

    protected function userMatchesScope(User $user, string $tabSessionScope): bool
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        return match ($tabSessionScope) {
            'admin' => in_array($role, ['admin', 'administrator'], true),
            'employee' => $role === 'employee',
            default => true,
        };
    }
}
