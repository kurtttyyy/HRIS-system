<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Northeastern College | HRMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --auth-gap: 2rem;
            --auth-switch-duration: 780ms;
            --auth-switch-ease: cubic-bezier(0.16, 0.84, 0.24, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #14532d 0%, #15803d 45%, #4ade80 100%);
            overflow-x: hidden;
        }

        .auth-stage {
            perspective: 1800px;
        }

        .auth-grid {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: var(--auth-gap);
        }

        @media (min-width: 1024px) {
            .auth-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .auth-card {
                will-change: transform, opacity;
                transition:
                    transform var(--auth-switch-duration) var(--auth-switch-ease),
                    opacity 360ms ease,
                    box-shadow 360ms ease;
            }

            .auth-stage.is-register .auth-showcase-card {
                transform: translateX(calc(100% + var(--auth-gap)));
            }

            .auth-stage.is-register .auth-form-card {
                transform: translateX(calc(-100% - var(--auth-gap)));
            }
        }

        .auth-panel {
            transition:
                opacity 280ms ease,
                transform 420ms ease;
        }

        .auth-login-panel,
        .auth-register-panel {
            grid-area: 1 / 1;
        }

        .auth-form-stack {
            display: grid;
        }

        .auth-stage.is-register .auth-login-panel {
            opacity: 0;
            pointer-events: none;
            transform: translateY(16px);
        }

        .auth-stage:not(.is-register) .auth-register-panel {
            opacity: 0;
            pointer-events: none;
            transform: translateY(16px);
        }

        .auth-stage.is-register .auth-register-panel,
        .auth-stage:not(.is-register) .auth-login-panel {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        @media (max-width: 1023px) {
            .auth-stage.is-register .auth-showcase-card,
            .auth-stage.is-register .auth-form-card {
                transform: none;
            }
        }
    </style>
</head>
<body>
@php
    $isRegister = ($mode ?? 'login') === 'register';
    $tabSession = trim((string) request()->query('tab_session', ''));
    $authRatingValue = is_null($companyRating ?? null) ? 0.0 : max(0, min(5, (float) $companyRating));
    $authRatingCount = (int) ($ratingCount ?? 0);
@endphp

<div
    data-auth-root
    data-login-url="{{ route('login_display') }}"
    data-register-url="{{ route('register') }}"
    data-tab-session="{{ $tabSession }}"
    class="auth-stage min-h-screen flex items-center justify-center bg-gradient-to-br from-green-900 via-green-700 to-green-500 px-6 py-8 {{ $isRegister ? 'is-register' : '' }}"
>
    <div class="auth-grid w-full max-w-6xl">
        <section class="auth-card auth-showcase-card rounded-3xl bg-white/95 p-10 shadow-2xl flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-12 h-12 rounded-xl overflow-hidden flex items-center justify-center bg-white ring-1 ring-slate-100">
                        <img src="/images/logo.webp" alt="Northeastern College Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-900 text-lg">Northeastern College</h1>
                        <p class="text-sm text-gray-500">HR Management System</p>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-green-700">
                    {{ $isRegister ? 'Registration' : 'Sign In' }}
                </div>

                @if (!$isRegister)
                    <div class="mt-8">
                        <h2 class="text-3xl font-extrabold text-gray-900">Default Accounts</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-500">Choose an account to fill the login form.</p>

                        <div class="mt-6 grid gap-3">
                            @foreach ($defaultAccounts ?? [] as $defaultAccount)
                                <button
                                    type="button"
                                    class="group flex w-full items-center justify-between gap-3 rounded-2xl border border-emerald-100 bg-white px-4 py-4 text-left shadow-sm transition hover:border-emerald-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    data-default-account
                                    data-default-email="{{ $defaultAccount['email'] }}"
                                    data-default-password="{{ $defaultAccount['password'] }}"
                                >
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold text-gray-900">{{ $defaultAccount['label'] }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ $defaultAccount['email'] }}</span>
                                    </span>
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 group-hover:bg-emerald-700 group-hover:text-white">
                                        {{ $defaultAccount['role'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <h2 class="mt-6 text-4xl font-extrabold text-gray-900 mb-4">
                        Streamline Your Workforce
                    </h2>

                    <p class="text-gray-600 mb-8 leading-relaxed">
                        Powerful HRIS platform designed to simplify employee management,
                        attendance tracking, and HR operations for modern businesses.
                    </p>

                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold">&#10003;</div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Employee Management</h3>
                                <p class="text-sm text-gray-500">Centralized employee records and comprehensive profiles.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">&#10003;</div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Attendance Tracking</h3>
                                <p class="text-sm text-gray-500">Monitor time-in, time-out, and attendance records in real time.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold">&#10003;</div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Analytics & Reports</h3>
                                <p class="text-sm text-gray-500">Real-time insights and comprehensive reporting.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($isRegister)
                <div class="grid grid-cols-3 gap-6 mt-10 pt-8 border-t">
                    <div>
                        <p class="text-2xl font-bold text-gray-900">50K+</p>
                        <p class="text-sm text-gray-500">Active Users</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">99.9%</p>
                        <p class="text-sm text-gray-500">Uptime</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($authRatingValue, 1) }}/5</p>
                        <p class="text-sm text-gray-500">{{ $authRatingCount > 0 ? number_format($authRatingCount).' Ratings' : 'User Rating' }}</p>
                    </div>
                </div>
            @endif
        </section>

        <section class="auth-card auth-form-card rounded-3xl bg-white p-10 shadow-2xl flex items-center">
            <div class="auth-form-stack w-full max-w-md mx-auto">
                <div class="auth-panel auth-login-panel">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                    <p class="text-gray-500 mb-8">Sign in to continue to your account</p>

                    @if (session('status') && (($mode ?? 'login') === 'login'))
                        <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->has('email'))
                        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first('email') }}
                        </div>
                    @endif

                    <form class="space-y-6" method="POST" action="{{ route('login') }}" data-login-form>
                        @csrf
                        <input type="hidden" name="tab_session" value="{{ $tabSession }}">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none" data-login-email>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700">Password</label>
                            <div class="relative mt-2">
                                <input type="password" name="password" placeholder="........" class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none" data-password-input data-login-password>
                                <button type="button" class="absolute inset-y-0 right-3 inline-flex items-center text-gray-400 transition hover:text-green-700" data-password-toggle aria-label="Show password">
                                    <svg data-password-icon-show xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg data-password-icon-hide xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.585 10.587A2 2 0 0 0 13.414 13.4" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a10.94 10.94 0 0 1-4.043 5.138M6.228 6.228A10.945 10.945 0 0 0 2.458 12c1.274 4.057 5.065 7 9.542 7a10.94 10.94 0 0 0 5.772-1.686" />
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-3 text-right">
                                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-green-700 hover:underline">
                                    Forgot password?
                                </a>
                            </div>
                        </div>

                        <button class="w-full py-3 rounded-xl bg-gradient-to-r from-green-800 via-green-600 to-green-800 text-white font-semibold hover:opacity-95 transition">
                            Sign In
                        </button>
                    </form>

                    <p class="text-center text-sm text-gray-500 mt-8">
                        Don't have an account?
                        <a href="{{ route('register') }}" data-auth-target="register" class="text-green-800 font-semibold hover:underline">
                            Create one now
                        </a>
                    </p>
                </div>

                <div class="auth-panel auth-register-panel">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Account</h2>
                    <p class="text-gray-500 mb-8">Register to access the HRMS</p>

                    @if ($errors->has('confirmation_password') || ($errors->has('email') && (($mode ?? 'login') === 'register')))
                        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first('confirmation_password') ?: $errors->first('email') }}
                        </div>
                    @endif

                    <form class="space-y-5" method="POST" action="{{ route('register.store') }}">
                        @csrf
                        <input type="hidden" name="tab_session" value="{{ $tabSession }}">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <input type="text" placeholder="First Name" name="first_name" value="{{ old('first_name') }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none">
                            <input type="text" placeholder="Middle Name" name="middle_name" value="{{ old('middle_name') }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none">
                        </div>

                        <input type="text" placeholder="Last Name" name="last_name" value="{{ old('last_name') }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none">
                        <input type="email" placeholder="Email Address" name="email" value="{{ old('email') }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none">
                        <div class="relative">
                            <input type="password" placeholder="Password" name="password" class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none" data-password-input>
                            <button type="button" class="absolute inset-y-0 right-3 inline-flex items-center text-gray-400 transition hover:text-green-700" data-password-toggle aria-label="Show password">
                                <svg data-password-icon-show xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-password-icon-hide xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.585 10.587A2 2 0 0 0 13.414 13.4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a10.94 10.94 0 0 1-4.043 5.138M6.228 6.228A10.945 10.945 0 0 0 2.458 12c1.274 4.057 5.065 7 9.542 7a10.94 10.94 0 0 0 5.772-1.686" />
                                </svg>
                            </button>
                        </div>
                        <div class="relative">
                            <input type="password" placeholder="Confirm Password" name="confirmation_password" class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-600 focus:outline-none" data-password-input>
                            <button type="button" class="absolute inset-y-0 right-3 inline-flex items-center text-gray-400 transition hover:text-green-700" data-password-toggle aria-label="Show password">
                                <svg data-password-icon-show xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-password-icon-hide xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.585 10.587A2 2 0 0 0 13.414 13.4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a10.94 10.94 0 0 1-4.043 5.138M6.228 6.228A10.945 10.945 0 0 0 2.458 12c1.274 4.057 5.065 7 9.542 7a10.94 10.94 0 0 0 5.772-1.686" />
                                </svg>
                            </button>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-green-800 via-green-600 to-green-800 text-white font-semibold py-3 rounded-xl transition hover:opacity-95">
                            Create Account
                        </button>
                    </form>

                    <p class="text-center text-sm text-gray-500 mt-8">
                        Already have an account?
                        <a href="{{ route('login_display') }}" data-auth-target="login" class="text-green-700 font-semibold hover:underline">
                            Sign in
                        </a>
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-auth-root]');
        if (!root) {
            return;
        }

        const withTabSession = (url, tabSession) => {
            const nextUrl = new URL(url, window.location.origin);
            if (tabSession) {
                nextUrl.searchParams.set('tab_session', tabSession);
            } else {
                nextUrl.searchParams.delete('tab_session');
            }
            return nextUrl.toString();
        };

        let tabSession = root.getAttribute('data-tab-session') || '';
        if (!tabSession) {
            try {
                tabSession = window.sessionStorage.getItem('auth_tab_session') || '';
            } catch (error) {
                tabSession = '';
            }
        }

        if (!tabSession) {
            if (window.crypto?.randomUUID) {
                tabSession = window.crypto.randomUUID();
            } else {
                tabSession = `tab-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
            }
        }

        try {
            window.sessionStorage.setItem('auth_tab_session', tabSession);
        } catch (error) {
        }

        root.setAttribute('data-tab-session', tabSession);

        const loginUrl = withTabSession(root.getAttribute('data-login-url') || '/login', tabSession);
        const registerUrl = withTabSession(root.getAttribute('data-register-url') || '/register', tabSession);

        document.querySelectorAll('input[name="tab_session"]').forEach((input) => {
            input.value = tabSession;
        });

        document.querySelectorAll('[data-auth-target="login"]').forEach((link) => {
            link.setAttribute('href', loginUrl);
        });

        document.querySelectorAll('[data-auth-target="register"]').forEach((link) => {
            link.setAttribute('href', registerUrl);
        });

        const currentUrl = new URL(window.location.href);
        if (currentUrl.searchParams.get('tab_session') !== tabSession) {
            currentUrl.searchParams.set('tab_session', tabSession);
            window.history.replaceState({ mode: root.classList.contains('is-register') ? 'register' : 'login' }, '', currentUrl.toString());
        }

        const setMode = (mode, pushHistory = true) => {
            const isRegister = mode === 'register';
            root.classList.toggle('is-register', isRegister);

            if (pushHistory) {
                const targetUrl = isRegister ? registerUrl : loginUrl;
                if (window.location.href !== targetUrl) {
                    window.history.pushState({ mode }, '', targetUrl);
                }
            }
        };

        document.querySelectorAll('[data-auth-target]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                setMode(link.getAttribute('data-auth-target') || 'login');
            });
        });

        document.querySelectorAll('[data-default-account]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = document.querySelector('[data-login-form]');
                const emailInput = form ? form.querySelector('[data-login-email]') : null;
                const passwordInput = form ? form.querySelector('[data-login-password]') : null;

                if (!form || !emailInput || !passwordInput) {
                    return;
                }

                emailInput.value = button.getAttribute('data-default-email') || '';
                passwordInput.value = button.getAttribute('data-default-password') || '';
                passwordInput.focus();
            });
        });

        window.addEventListener('popstate', () => {
            const nextMode = window.location.pathname === new URL(registerUrl, window.location.origin).pathname
                ? 'register'
                : 'login';

            setMode(nextMode, false);
        });

        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const wrapper = toggle.parentElement;
                const input = wrapper ? wrapper.querySelector('[data-password-input]') : null;
                const showIcon = toggle.querySelector('[data-password-icon-show]');
                const hideIcon = toggle.querySelector('[data-password-icon-hide]');
                if (!input || !showIcon || !hideIcon) {
                    return;
                }

                const showing = input.getAttribute('type') === 'text';
                input.setAttribute('type', showing ? 'password' : 'text');
                showIcon.classList.toggle('hidden', !showing);
                hideIcon.classList.toggle('hidden', showing);
                toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });
    })();
</script>

</body>
</html>
