<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { transition: margin-left 0.3s ease; }
        main { transition: margin-left 0.3s ease; }
        aside:not(:hover) ~ main { margin-left: 4rem; }
        aside:hover ~ main { margin-left: 14rem; }

        @media screen and (max-width: 767px) {
            html, body { max-width: 100%; overflow-x: hidden; }
            aside ~ main {
                width: 100% !important;
                min-width: 0 !important;
                margin-left: 0 !important;
            }
            #employee-notifications-page {
                padding: 0.75rem !important;
            }
            #employee-notification-inbox {
                border-radius: 1rem !important;
                box-shadow: none;
            }
            #employee-notification-inbox > div > aside {
                padding: 0.75rem !important;
            }
            #employee-notification-inbox > div > aside > div:first-child {
                display: flex;
                min-height: 3.25rem;
                align-items: center;
                gap: 0.65rem;
                padding: 0.65rem 0.75rem;
                border-radius: 0.75rem;
                box-shadow: none;
            }
            #employee-notification-inbox > div > aside > div:first-child p:first-child {
                font-size: 0.55rem;
                letter-spacing: 0.14em;
            }
            #employee-notification-inbox > div > aside > div:first-child p:nth-child(2) {
                margin-top: 0;
                font-size: 1.15rem;
            }
            #employee-notification-inbox > div > aside > div:first-child p:last-child {
                margin-top: 0;
                margin-left: auto;
                font-size: 0.62rem;
            }
            #employee-notification-inbox > div > aside > div:nth-child(2) {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.4rem;
                margin-top: 0.6rem;
            }
            #employee-notification-inbox .notification-filter {
                min-width: 0;
                justify-content: center;
                gap: 0.3rem;
                padding: 0.5rem 0.35rem;
                border-radius: 0.65rem;
                font-size: 0.58rem;
            }
            #employee-notification-inbox .notification-filter > span:first-child {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            #employee-notification-inbox .notification-filter i {
                margin-right: 0.2rem;
            }
            #employee-notification-inbox > div > aside > div:last-child {
                display: none;
            }
            #employee-notification-inbox .employee-notification-list-header {
                padding: 0.65rem 0.75rem;
            }
            #employee-notification-inbox .employee-notification-list-header > div > div:first-child p,
            #employee-notification-inbox .employee-notification-list-header > div > div:last-child {
                display: none;
            }
            #employee-notification-inbox .employee-notification-list-header h2 {
                margin-top: 0;
                font-size: 1rem;
            }
            #employee-notification-inbox #notification-results-label {
                padding: 0.45rem 0.75rem;
                font-size: 0.52rem;
                letter-spacing: 0.1em;
            }
            #employee-notification-inbox .notification-row {
                padding: 0.75rem;
                gap: 0.5rem;
            }
            #employee-notification-inbox #notification-empty-state,
            #employee-notification-inbox #notification-filter-empty {
                padding: 1.5rem 0.75rem;
            }
        }
    </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#ecfdf5,_#eff6ff_32%,_#f8fafc_72%)] text-slate-900">
@php
    $notificationItems = collect($notificationItems ?? []);
    $notificationStats = $notificationStats ?? ['total' => 0, 'leave' => 0, 'payslip' => 0, 'attendance' => 0, 'resignation' => 0];
    $toneClasses = [
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'rose' => 'bg-rose-100 text-rose-700',
        'violet' => 'bg-violet-100 text-violet-700',
        'sky' => 'bg-sky-100 text-sky-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
    $iconClasses = [
        'Leave' => 'fa-calendar-check-o',
        'Payslip' => 'fa-money',
        'Attendance' => 'fa-clock-o',
        'Resignation' => 'fa-file-text-o',
    ];
    $categoryKeyMap = [
        'Leave' => 'leave',
        'Payslip' => 'payslip',
        'Attendance' => 'attendance',
        'Resignation' => 'resignation',
    ];
@endphp

<div class="flex min-h-screen">
    @include('components.employeeSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        @include('components.employeeHeader.dashboardHeader', [
            'name' => trim((string) ($user->first_name ?? '')),
            'notifications' => 0,
        ])

        <div id="employee-notifications-page" class="p-4 pt-20 md:p-8">
            <section id="employee-notification-inbox" class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
                <div class="grid grid-cols-1 xl:grid-cols-[260px_1fr]">
                    <aside class="border-b border-slate-200 bg-[linear-gradient(180deg,#f8fafc,#f1f5f9)] p-5 xl:border-b-0 xl:border-r">
                        <div class="rounded-[1.4rem] bg-slate-900 px-4 py-4 text-white shadow-lg">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-300">Inbox</p>
                            <p class="mt-2 text-3xl font-black">{{ (int) ($notificationStats['total'] ?? 0) }}</p>
                            <p class="mt-1 text-sm text-slate-300">recent update{{ (int) ($notificationStats['total'] ?? 0) === 1 ? '' : 's' }}</p>
                        </div>

                        <div class="mt-6 space-y-2">
                            <button type="button" data-filter="all" class="notification-filter flex w-full items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                <span><i class="fa fa-inbox mr-2"></i>All mail</span>
                                <span>{{ (int) ($notificationStats['total'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="leave" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa fa-calendar-check-o mr-2 text-emerald-600"></i>Leave</span>
                                <span>{{ (int) ($notificationStats['leave'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="payslip" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa fa-money mr-2 text-violet-600"></i>Payslips</span>
                                <span>{{ (int) ($notificationStats['payslip'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="attendance" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa fa-clock-o mr-2 text-amber-600"></i>Attendance</span>
                                <span>{{ (int) ($notificationStats['attendance'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="resignation" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa fa-file-text-o mr-2 text-slate-600"></i>Resignation</span>
                                <span>{{ (int) ($notificationStats['resignation'] ?? 0) }}</span>
                            </button>
                        </div>

                        <div class="mt-8 rounded-[1.4rem] border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Quick Actions</p>
                            <div class="mt-4 space-y-2">
                                <a href="{{ route('employee.employeeLeave') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-blue-50 hover:text-blue-700">Open Leave Requests</a>
                                <a href="{{ route('employee.employeePayslip') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-violet-50 hover:text-violet-700">Review Payslips</a>
                                <a href="{{ route('employee.employeeHome') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-700">Back to Dashboard</a>
                            </div>
                        </div>
                    </aside>

                    <div class="min-w-0">
                        <div class="employee-notification-list-header border-b border-slate-200 bg-white px-5 py-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Notification Mail</p>
                                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Inbox</h2>
                                </div>
                                <div class="flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500">
                                    <i class="fa fa-search"></i>
                                    <span>Search notifications</span>
                                </div>
                            </div>
                        </div>

                        <div id="notification-results-label" class="border-b border-slate-200 bg-slate-50/70 px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                            {{ $notificationItems->count() }} conversation{{ $notificationItems->count() === 1 ? '' : 's' }}
                        </div>

                        <div id="notification-list" class="divide-y divide-slate-200">
                            @forelse ($notificationItems as $item)
                                @php
                                    $tone = $toneClasses[$item['tone'] ?? 'slate'] ?? $toneClasses['slate'];
                                    $itemDate = $item['date'] ?? null;
                                    $iconClass = $iconClasses[$item['category'] ?? ''] ?? 'fa-bell-o';
                                    $itemFilter = $categoryKeyMap[$item['category'] ?? ''] ?? 'other';
                                @endphp
                                <a
                                    href="{{ $item['href'] ?? '#' }}"
                                    data-category="{{ $itemFilter }}"
                                    data-notification-id="{{ md5(($item['category'] ?? 'update').'|'.($item['title'] ?? '').'|'.($item['message'] ?? '').'|'.optional($itemDate)->format('Y-m-d H:i:s')) }}"
                                    class="notification-row group grid grid-cols-1 gap-3 bg-white px-5 py-4 transition hover:bg-slate-50 md:grid-cols-[44px_170px_minmax(0,1fr)_110px] md:items-center"
                                >
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $tone }}">
                                        <i class="fa {{ $iconClass }}"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">
                                            {{ $item['category'] ?? 'Update' }}
                                        </p>
                                        <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $tone }}">
                                            {{ $item['badge'] ?? 'Notice' }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="notification-title truncate text-sm font-black text-slate-900 group-hover:text-emerald-700">
                                                {{ $item['title'] ?? 'Notification' }}
                                            </span>
                                            <span class="hidden text-slate-300 md:inline">-</span>
                                            <span class="truncate text-sm text-slate-500">
                                                {{ $item['message'] ?? '' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium text-slate-400 md:text-right">
                                        {{ $itemDate ? $itemDate->diffForHumans() : '-' }}
                                    </div>
                                </a>
                            @empty
                                <div id="notification-empty-state" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                                        <i class="fa fa-bell-slash-o text-2xl"></i>
                                    </div>
                                    <h3 class="mt-5 text-xl font-bold text-slate-900">Your inbox is quiet.</h3>
                                    <p class="mt-2 text-sm text-slate-500">New employee updates will appear here like an inbox feed.</p>
                                </div>
                            @endforelse
                            <div id="notification-filter-empty" class="hidden px-6 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                                    <i class="fa fa-search text-2xl"></i>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-slate-900">No notifications in this category.</h3>
                                <p class="mt-2 text-sm text-slate-500">Try another inbox filter to view other updates.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
    const sidebar = document.querySelector('aside');
    const main = document.querySelector('main');

    if (sidebar && main) {
        sidebar.addEventListener('mouseenter', function() {
            main.classList.remove('ml-16');
            main.classList.add('ml-56');
        });

        sidebar.addEventListener('mouseleave', function() {
            main.classList.remove('ml-56');
            main.classList.add('ml-16');
        });
    }

    (function () {
        const filterButtons = Array.from(document.querySelectorAll('.notification-filter'));
        const items = Array.from(document.querySelectorAll('#notification-list > a[data-category]'));
        const resultsLabel = document.getElementById('notification-results-label');
        const filterEmpty = document.getElementById('notification-filter-empty');
        const defaultEmpty = document.getElementById('notification-empty-state');

        if (!filterButtons.length || !resultsLabel) {
            return;
        }

        const updateActiveButton = (activeFilter) => {
            filterButtons.forEach((button) => {
                const isActive = button.getAttribute('data-filter') === activeFilter;
                button.classList.toggle('bg-emerald-50', isActive);
                button.classList.toggle('text-emerald-700', isActive);
                button.classList.toggle('font-semibold', isActive);
                button.classList.toggle('text-slate-600', !isActive);
            });
        };

        const applyFilter = (filterValue) => {
            let visibleCount = 0;

            items.forEach((item) => {
                const matches = filterValue === 'all' || item.getAttribute('data-category') === filterValue;
                item.classList.toggle('hidden', !matches);
                if (matches) {
                    visibleCount += 1;
                }
            });

            resultsLabel.textContent = `${visibleCount} conversation${visibleCount === 1 ? '' : 's'}`;

            if (defaultEmpty) {
                defaultEmpty.classList.toggle('hidden', items.length > 0 || visibleCount !== 0);
            }

            if (filterEmpty) {
                filterEmpty.classList.toggle('hidden', visibleCount !== 0 || items.length === 0);
            }

            updateActiveButton(filterValue);
        };

        filterButtons.forEach((button) => {
            button.addEventListener('click', function () {
                applyFilter(this.getAttribute('data-filter') || 'all');
            });
        });

        applyFilter('all');
    })();

    (function () {
        const storageKey = 'employee_notifications_read_v1';
        const unreadKey = 'employee_notifications_unread_v1';
        const totalKey = 'employee_notifications_total_v1';
        const notificationRows = Array.from(document.querySelectorAll('.notification-row[data-notification-id]'));

        if (!notificationRows.length) {
            localStorage.setItem(unreadKey, '0');
            return;
        }

        const readLookup = (() => {
            try {
                const raw = localStorage.getItem(storageKey);
                const parsed = raw ? JSON.parse(raw) : {};
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        })();

        const saveLookup = () => {
            try {
                localStorage.setItem(storageKey, JSON.stringify(readLookup));
            } catch (error) {
            }
        };

        const updateUnreadCount = () => {
            const unreadCount = notificationRows.reduce((count, row) => {
                const id = row.getAttribute('data-notification-id');
                return count + (id && !readLookup[id] ? 1 : 0);
            }, 0);

            try {
                localStorage.setItem(unreadKey, String(unreadCount));
                localStorage.setItem(totalKey, String(notificationRows.length));
            } catch (error) {
            }

            document.querySelectorAll('[data-employee-notification-badge]').forEach((badge) => {
                if (unreadCount > 0) {
                    badge.textContent = String(unreadCount);
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
        };

        const applyReadState = (row, isRead) => {
            row.classList.toggle('bg-emerald-50', !isRead);
            row.classList.toggle('hover:bg-emerald-100', !isRead);
            row.classList.toggle('border-l-4', !isRead);
            row.classList.toggle('border-emerald-400', !isRead);
            row.classList.toggle('bg-white', isRead);
            row.classList.toggle('opacity-90', isRead);

            const title = row.querySelector('.notification-title');
            if (title) {
                title.classList.toggle('font-black', !isRead);
                title.classList.toggle('font-semibold', isRead);
                title.classList.toggle('text-slate-900', !isRead);
                title.classList.toggle('text-slate-700', isRead);
            }
        };

        notificationRows.forEach((row) => {
            const id = row.getAttribute('data-notification-id');
            if (!id) {
                return;
            }

            applyReadState(row, Boolean(readLookup[id]));

            row.addEventListener('click', function () {
                readLookup[id] = true;
                saveLookup();
                applyReadState(row, true);
                updateUnreadCount();
            });
        });

        updateUnreadCount();
    })();
</script>
</body>
</html>
