@php
    $headerUser = auth()->user();
    $displayName = $name ?? trim((string) ($headerUser?->first_name ?? ''));
    $notificationCount = (int) ($notifications ?? 0);
    $headerBadge = trim((string) ($badge ?? 'Employee Dashboard'));
    $headerSubtitle = trim((string) ($subtitle ?? 'Track requests, review updates, and move through your workday from one clean workspace.'));
    $statusChip = trim((string) ($status_chip ?? 'Ready Today'));
    $currentHour = now()->hour;

    if ($currentHour < 12) {
        $greeting = 'Good morning';
    } elseif ($currentHour < 18) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
@endphp

<style>
    @keyframes employee-hand-wave {
        0%, 100% {
            transform: rotate(0deg);
        }
        15% {
            transform: rotate(14deg);
        }
        30% {
            transform: rotate(-8deg);
        }
        45% {
            transform: rotate(14deg);
        }
        60% {
            transform: rotate(-4deg);
        }
        75% {
            transform: rotate(10deg);
        }
    }

    @keyframes employee-header-float {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    .employee-dashboard-header {
        transition: background-color 0.28s ease, box-shadow 0.28s ease;
    }

    .employee-dashboard-header__card {
        transition: padding 0.28s ease, border-radius 0.28s ease, background-color 0.28s ease, box-shadow 0.28s ease, transform 0.28s ease;
    }

    .employee-dashboard-header__title {
        transition: font-size 0.28s ease, transform 0.28s ease;
    }

    .employee-dashboard-header__subtitle,
    .employee-dashboard-header__chips,
    .employee-dashboard-header__badge {
        transition: opacity 0.24s ease, transform 0.24s ease;
    }

    .employee-hand-wave {
        display: inline-block;
        transform-origin: 70% 70%;
        animation: employee-hand-wave 1.8s ease-in-out infinite;
    }

    .employee-header-orb {
        animation: employee-header-float 6s ease-in-out infinite;
        transition: opacity 0.28s ease, transform 0.28s ease;
    }

    .employee-dashboard-header.is-scrolled {
        box-shadow: none;
    }

    .employee-dashboard-header.is-scrolled .employee-dashboard-header__card {
        border-radius: 1.35rem;
        background-color: rgba(255, 255, 255, 0.9);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        transform: scale(0.985);
    }

    .employee-dashboard-header.is-scrolled .employee-dashboard-header__title {
        transform: translateY(-2px);
    }

    .employee-dashboard-header.is-scrolled .employee-dashboard-header__subtitle {
        opacity: 0.68;
        transform: translateY(-4px);
    }

    .employee-dashboard-header.is-scrolled .employee-dashboard-header__chips,
    .employee-dashboard-header.is-scrolled .employee-dashboard-header__badge {
        transform: translateY(-4px);
    }

    .employee-dashboard-header.is-scrolled .employee-hand-wave {
        animation-play-state: paused;
    }

    .employee-dashboard-header.is-scrolled .employee-header-orb {
        opacity: 0.55;
        transform: scale(0.9);
    }
</style>

<header id="employee-dashboard-header" class="employee-dashboard-header sticky top-0 z-40 border-b border-transparent bg-transparent px-4 py-4 shadow-none backdrop-blur md:px-8">
    <div class="employee-dashboard-header__card relative rounded-[1.75rem] border border-white/80 bg-white/80 px-5 py-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] md:px-7">
        <div class="employee-header-orb absolute -left-8 top-4 h-24 w-24 rounded-full bg-emerald-200/40 blur-2xl"></div>
        <div class="employee-header-orb absolute right-8 top-0 h-20 w-20 rounded-full bg-sky-200/40 blur-2xl"></div>

        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="employee-dashboard-header__badge inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-700">
                    {{ $headerBadge !== '' ? $headerBadge : 'Employee Dashboard' }}
                </div>
                <h2 class="employee-dashboard-header__title mt-3 text-2xl font-black tracking-tight text-slate-900 md:text-3xl">
                    {{ $greeting }}{{ $displayName !== '' ? ', '.$displayName : '' }}
                    <span class="employee-hand-wave text-amber-500">&#128075;</span>
                </h2>
                <p class="employee-dashboard-header__subtitle mt-1 max-w-2xl text-sm leading-6 text-slate-600 md:text-base">
                    {{ $headerSubtitle !== '' ? $headerSubtitle : 'Track requests, review updates, and move through your workday from one clean workspace.' }}
                </p>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="employee-dashboard-header__chips flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <span class="rounded-full bg-emerald-100 px-3 py-2 text-emerald-700">{{ $statusChip !== '' ? $statusChip : 'Ready Today' }}</span>
                    <span class="rounded-full bg-sky-100 px-3 py-2 text-sky-700">{{ now()->format('M d, Y') }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('employee.employeeNotifications') }}" class="relative flex h-12 w-12 cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:text-emerald-700" aria-label="Open notifications">
                        <span data-employee-notification-badge data-fallback-count="{{ $notificationCount }}" class="{{ $notificationCount > 0 ? '' : 'hidden ' }}pointer-events-none absolute right-0 top-0 flex h-5 min-w-[1.25rem] -translate-y-1/4 translate-x-1/4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">
                            {{ $notificationCount }}
                        </span>
                        <i class="pointer-events-none fa fa-bell text-lg"></i>
                    </a>

                    <div class="relative group">
                        <button class="p-2.5 text-slate-600 transition hover:rounded-full hover:bg-slate-100">
                            <i class="fa fa-user fa-2x"></i>
                        </button>

                        <div class="invisible absolute right-0 z-50 mt-3 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-xl transition-all duration-200 group-hover:visible group-hover:opacity-100">
                            <a href="{{ route('employee.employeeProfile', array_filter(['tab_session' => request()->query('tab_session')])) }}"
                               class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">
                                <i class="fa fa-user"></i>
                                My Profile
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                @if (request()->filled('tab_session'))
                                    <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
                                @endif
                                <button
                                    type="submit"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-rose-600 hover:bg-rose-50"
                                >
                                    <i class="fa fa-sign-out"></i>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    (function () {
        const header = document.getElementById('employee-dashboard-header');
        if (!header) {
            return;
        }

        let isScrolled = false;

        const updateHeaderOnScroll = () => {
            const nextScrolled = window.scrollY > 24;
            if (nextScrolled === isScrolled) {
                return;
            }

            isScrolled = nextScrolled;
            header.classList.toggle('is-scrolled', isScrolled);
        };

        updateHeaderOnScroll();
        window.addEventListener('scroll', updateHeaderOnScroll, { passive: true });
    })();

    (function () {
        const badge = document.querySelector('[data-employee-notification-badge]');
        const summaryUrl = @json(route('employee.employeeNotifications.summary'));
        if (!badge || !summaryUrl) {
            return;
        }

        const readKey = 'employee_notifications_read_v1';
        const unreadKey = 'employee_notifications_unread_v1';
        const fallbackCount = Number.parseInt(badge.getAttribute('data-fallback-count') || '0', 10) || 0;
        const renderBadge = (count) => {
            if (count > 0) {
                badge.textContent = String(count);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        };

        const computeUnreadCount = (items) => {
            let readLookup = {};
            try {
                const raw = localStorage.getItem(readKey);
                const parsed = raw ? JSON.parse(raw) : {};
                readLookup = parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                readLookup = {};
            }

            return (Array.isArray(items) ? items : []).reduce((count, item) => {
                const id = item?.id ? String(item.id) : '';
                return count + (id && !readLookup[id] ? 1 : 0);
            }, 0);
        };

        const syncBadge = async () => {
            try {
                const response = await fetch(summaryUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Unable to load notification summary.');
                }

                const payload = await response.json();
                const unreadCount = computeUnreadCount(payload?.items ?? []);
                localStorage.setItem(unreadKey, String(unreadCount));
                renderBadge(unreadCount);
            } catch (error) {
                const storedUnread = Number.parseInt(localStorage.getItem(unreadKey) || '', 10);
                const nextCount = Number.isFinite(storedUnread) ? storedUnread : fallbackCount;
                renderBadge(nextCount);
            }
        };

        syncBadge();
        window.addEventListener('storage', function (event) {
            if (event.key === unreadKey) {
                const nextCount = Number.parseInt(event.newValue || '', 10);
                renderBadge(Number.isFinite(nextCount) ? nextCount : fallbackCount);
            }
        });
        window.setInterval(syncBadge, 30000);
    })();
</script>
