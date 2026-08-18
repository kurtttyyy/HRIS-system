<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications | PeopleHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; transition: margin-left 0.3s ease; }
        main { transition: margin-left 0.3s ease; }
        aside ~ main { margin-left: 16rem; }
        .admin-display {
            font-family: "Arial Black", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            letter-spacing: -0.03em;
        }
        .admin-kicker {
            letter-spacing: 0.22em;
        }
        html[data-theme="dark"] #admin-notification-inbox {
            background: #111c30 !important;
            border-color: #334155 !important;
        }
        html[data-theme="dark"] #admin-notification-inbox > div > aside {
            background: #0f172a !important;
            border-color: #334155 !important;
        }
        html[data-theme="dark"] #admin-notification-inbox .notification-row {
            background: #111c30 !important;
            color: #cbd5e1;
        }
        html[data-theme="dark"] #admin-notification-inbox .notification-row[class*="bg-emerald-50"] {
            background: #132a2a !important;
        }
        html[data-theme="dark"] #admin-notification-inbox .notification-row:hover {
            background: #1e293b !important;
            box-shadow: inset 3px 0 0 #34d399;
        }
        html[data-theme="dark"] #admin-notification-inbox .notification-filter {
            color: #cbd5e1 !important;
        }
        html[data-theme="dark"] #admin-notification-inbox .notification-filter.bg-emerald-50 {
            background: #16382f !important;
            color: #a7f3d0 !important;
        }
        html[data-theme="dark"] #admin-notification-inbox .notification-filter:hover,
        html[data-theme="dark"] #admin-notification-inbox aside a:hover {
            background: #1e293b !important;
            box-shadow: none !important;
            filter: none !important;
            transform: none !important;
        }
        @media (max-width: 767px) {
            html, body { max-width: 100%; overflow-x: hidden; }
            aside ~ main {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin-left: 0 !important;
            }
            main > div:last-of-type {
                width: 100%;
                padding: .75rem !important;
                padding-top: .5rem !important;
            }
            #admin-notification-inbox {
                width: 100%;
                max-width: 100%;
                border-radius: 1.25rem;
                box-shadow: none;
            }
            #admin-notification-inbox > div { min-width: 0; }
            #admin-notification-inbox > div > aside {
                min-width: 0;
                padding: .875rem;
            }
            #admin-notification-inbox > div > aside > div:first-child {
                padding: .75rem;
                border-radius: .875rem;
                box-shadow: none;
            }
            #admin-notification-inbox > div > aside > div:nth-child(2) {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: .4rem;
                margin-top: .75rem;
            }
            #admin-notification-inbox .notification-filter {
                min-width: 0;
                justify-content: center;
                gap: .25rem;
                padding: .55rem .35rem;
                border-radius: .7rem;
                font-size: .62rem;
            }
            #admin-notification-inbox .notification-filter i { margin-right: .2rem; }
            #admin-notification-inbox > div > aside > div:last-child { display: none; }
            #admin-notification-inbox #notification-empty-state,
            #admin-notification-inbox #notification-filter-empty {
                padding: 2rem .875rem;
            }
        }
    </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#f8fafc,_#eef2ff_40%,_#f8fafc_100%)] text-slate-900">
@php
    $adminNotificationItems = collect($adminNotificationItems ?? []);
    $adminNotificationStats = $adminNotificationStats ?? ['total' => 0, 'approvals' => 0, 'leave' => 0, 'hiring' => 0, 'requests' => 0, 'workforce' => 0];
    $toneClasses = [
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'rose' => 'bg-rose-100 text-rose-700',
        'violet' => 'bg-violet-100 text-violet-700',
        'sky' => 'bg-sky-100 text-sky-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
    $iconClasses = [
        'Approvals' => 'fa-user-check',
        'Leave' => 'fa-calendar-check',
        'Hiring' => 'fa-briefcase',
        'Requests' => 'fa-file-signature',
        'Workforce' => 'fa-building-user',
    ];
    $categoryKeyMap = [
        'Approvals' => 'approvals',
        'Leave' => 'leave',
        'Hiring' => 'hiring',
        'Requests' => 'requests',
        'Workforce' => 'workforce',
    ];
    $initialNotificationItems = $adminNotificationItems->map(function ($item) use ($categoryKeyMap) {
        $itemDate = $item['date'] ?? null;
        return [
            'id' => $item['id'] ?? null,
            'category' => $item['category'] ?? 'Update',
            'filter' => $categoryKeyMap[$item['category'] ?? ''] ?? 'other',
            'title' => $item['title'] ?? 'Notification',
            'message' => $item['message'] ?? '',
            'href' => $item['href'] ?? '#',
            'badge' => $item['badge'] ?? 'Notice',
            'tone' => $item['tone'] ?? 'slate',
            'date' => optional($itemDate)?->toIso8601String(),
            'date_human' => $itemDate ? \Carbon\Carbon::parse($itemDate)->diffForHumans(now(), ['parts' => 2]) : 'Live',
        ];
    })->values();
@endphp

<div class="flex min-h-screen">
    @include('components.adminSideBar')

    <main class="ml-16 min-w-0 flex-1 transition-all duration-300">
        @include('components.adminHeader.dashboardHeader', [
            'headerTitle' => 'Admin Notifications',
            'headerSubtitle' => 'Track approvals, leave requests, and workforce signals from one inbox.',
            'headerSearchPlaceholder' => 'Search notifications...',
            'adminNotificationItems' => $adminNotificationItems,
            'adminNotificationStats' => $adminNotificationStats,
            'employee' => $employee ?? collect(),
            'departments' => $departments ?? collect(),
            'openPositionApplicationsCount' => $openPositionApplicationsCount ?? 0,
        ])

        <div class="min-w-0 p-4 pt-20 md:p-8">
            <section id="admin-notification-inbox" class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
                <div class="grid grid-cols-1 xl:grid-cols-[280px_1fr]">
                    <aside class="border-b border-slate-200 bg-[linear-gradient(180deg,#f8fafc,#f1f5f9)] p-5 xl:border-b-0 xl:border-r">
                        <div class="rounded-[1.4rem] bg-slate-900 px-4 py-4 text-white shadow-lg">
                            <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">Inbox</p>
                            <p class="admin-display mt-2 text-3xl text-white" data-stat-total>{{ (int) ($adminNotificationStats['total'] ?? 0) }}</p>
                            <p class="mt-1 text-sm text-slate-300">admin update{{ (int) ($adminNotificationStats['total'] ?? 0) === 1 ? '' : 's' }}</p>
                        </div>

                        <div class="mt-6 space-y-2">
                            <button type="button" data-filter="all" class="notification-filter flex w-full items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                <span><i class="fa-solid fa-inbox mr-2"></i>All mail</span>
                                <span data-stat-total>{{ (int) ($adminNotificationStats['total'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="approvals" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa-solid fa-user-check mr-2 text-emerald-600"></i>Approvals</span>
                                <span data-stat-approvals>{{ (int) ($adminNotificationStats['approvals'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="leave" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa-solid fa-calendar-check mr-2 text-amber-600"></i>Leave</span>
                                <span data-stat-leave>{{ (int) ($adminNotificationStats['leave'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="hiring" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa-solid fa-briefcase mr-2 text-sky-600"></i>Hiring</span>
                                <span data-stat-hiring>{{ (int) ($adminNotificationStats['hiring'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="requests" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa-solid fa-file-signature mr-2 text-rose-600"></i>Requests</span>
                                <span data-stat-requests>{{ (int) ($adminNotificationStats['requests'] ?? 0) }}</span>
                            </button>
                            <button type="button" data-filter="workforce" class="notification-filter flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-100">
                                <span><i class="fa-solid fa-building-user mr-2 text-slate-600"></i>Workforce</span>
                                <span data-stat-workforce>{{ (int) ($adminNotificationStats['workforce'] ?? 0) }}</span>
                            </button>
                        </div>

                        <div class="mt-8 rounded-[1.4rem] border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Quick Actions</p>
                            <div class="mt-4 space-y-2">
                                <a href="{{ route('admin.adminEmployee') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-700">Review Employee Queue</a>
                                <a href="{{ route('admin.adminLeaveManagement') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-amber-50 hover:text-amber-700">Open Leave Management</a>
                                <a href="{{ route('admin.adminApplicant') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-sky-50 hover:text-sky-700">Review Applicants</a>
                                <a href="{{ route('admin.adminHome') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Back to Dashboard</a>
                            </div>
                        </div>
                    </aside>

                    <div class="min-w-0">
                        <div class="border-b border-slate-200 bg-white px-5 py-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Notification Mail</p>
                                    <h2 class="admin-display mt-1 text-2xl text-slate-900">Inbox</h2>
                                </div>
                                <div class="flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500">
                                    <i class="fa-solid fa-bell"></i>
                                    <span>Admin workflow feed</span>
                                </div>
                            </div>
                        </div>

                        <div id="notification-results-label" class="border-b border-slate-200 bg-slate-50/70 px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                            {{ $adminNotificationItems->count() }} conversation{{ $adminNotificationItems->count() === 1 ? '' : 's' }}
                        </div>

                        <div id="notification-list" class="divide-y divide-slate-200">
                            @forelse ($adminNotificationItems as $item)
                                @php
                                    $tone = $toneClasses[$item['tone'] ?? 'slate'] ?? $toneClasses['slate'];
                                    $itemDate = $item['date'] ?? null;
                                    $iconClass = $iconClasses[$item['category'] ?? ''] ?? 'fa-bell';
                                    $itemFilter = $categoryKeyMap[$item['category'] ?? ''] ?? 'other';
                                @endphp
                                <a
                                    href="{{ $item['href'] ?? '#' }}"
                                    data-category="{{ $itemFilter }}"
                                    data-notification-id="{{ $item['id'] ?? md5(($item['category'] ?? 'update').'|'.($item['title'] ?? '').'|'.($item['message'] ?? '').'|'.optional($itemDate)->format('Y-m-d H:i:s')) }}"
                                    class="notification-row group grid grid-cols-1 gap-3 bg-white px-5 py-4 transition hover:bg-slate-50 md:grid-cols-[52px_170px_minmax(0,1fr)_110px] md:items-center"
                                >
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $tone }}">
                                        <i class="fa-solid {{ $iconClass }}"></i>
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
                                        {{ $itemDate ? $itemDate->diffForHumans() : 'Live' }}
                                    </div>
                                </a>
                            @empty
                                <div id="notification-empty-state" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                                        <i class="fa-regular fa-bell-slash text-2xl"></i>
                                    </div>
                                    <h3 class="mt-5 text-xl font-bold text-slate-900">Your admin inbox is quiet.</h3>
                                    <p class="mt-2 text-sm text-slate-500">New workforce updates will appear here as they come in.</p>
                                </div>
                            @endforelse
                            <div id="notification-filter-empty" class="hidden px-6 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                                    <i class="fa-solid fa-magnifying-glass text-2xl"></i>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-slate-900">No notifications in this category.</h3>
                                <p class="mt-2 text-sm text-slate-500">Try another inbox filter to view other admin updates.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
    (function () {
        const sidebar = document.querySelector('.admin-sidebar');
        const main = document.querySelector('main');
        const isDesktopViewport = () => window.matchMedia('(min-width: 1025px)').matches;

        if (sidebar && main) {
            const applyCollapsedSidebar = () => {
                if (!isDesktopViewport()) {
                    main.classList.remove('ml-16', 'ml-72');
                    return;
                }

                main.classList.remove('ml-72');
                main.classList.add('ml-16');
            };

            const applyExpandedSidebar = () => {
                if (!isDesktopViewport()) {
                    main.classList.remove('ml-16', 'ml-72');
                    return;
                }

                main.classList.remove('ml-16');
                main.classList.add('ml-72');
            };

            applyCollapsedSidebar();
            sidebar.addEventListener('mouseenter', applyExpandedSidebar);
            sidebar.addEventListener('mouseleave', applyCollapsedSidebar);
            window.addEventListener('resize', applyCollapsedSidebar);
        }

        const filterButtons = Array.from(document.querySelectorAll('.notification-filter'));
        const resultsLabel = document.getElementById('notification-results-label');
        const listContainer = document.getElementById('notification-list');
        const filterEmpty = document.getElementById('notification-filter-empty');
        const defaultEmpty = document.getElementById('notification-empty-state');
        const summaryUrl = @json(route('admin.adminNotifications.summary'));
        const storageKey = 'admin_notifications_read_v1';
        const unreadKey = 'admin_notifications_unread_v1';
        const totalKey = 'admin_notifications_total_v1';
        const statElements = {
            total: Array.from(document.querySelectorAll('[data-stat-total]')),
            approvals: Array.from(document.querySelectorAll('[data-stat-approvals]')),
            leave: Array.from(document.querySelectorAll('[data-stat-leave]')),
            hiring: Array.from(document.querySelectorAll('[data-stat-hiring]')),
            requests: Array.from(document.querySelectorAll('[data-stat-requests]')),
            workforce: Array.from(document.querySelectorAll('[data-stat-workforce]')),
        };
        const toneClasses = {
            emerald: 'bg-emerald-100 text-emerald-700',
            amber: 'bg-amber-100 text-amber-700',
            rose: 'bg-rose-100 text-rose-700',
            violet: 'bg-violet-100 text-violet-700',
            sky: 'bg-sky-100 text-sky-700',
            slate: 'bg-slate-100 text-slate-700',
        };
        const iconClasses = {
            approvals: 'fa-user-check',
            leave: 'fa-calendar-check',
            hiring: 'fa-briefcase',
            requests: 'fa-file-signature',
            workforce: 'fa-building-user',
            other: 'fa-bell',
        };
        const initialItems = @json($initialNotificationItems);
        const initialStats = @json($adminNotificationStats);

        if (!filterButtons.length || !resultsLabel || !listContainer) {
            return;
        }

        let items = Array.isArray(initialItems) ? initialItems : [];
        let activeFilter = 'all';
        let readLookup = {};

        try {
            const rawRead = localStorage.getItem(storageKey);
            const parsedRead = rawRead ? JSON.parse(rawRead) : {};
            readLookup = parsedRead && typeof parsedRead === 'object' ? parsedRead : {};
        } catch (error) {
            readLookup = {};
        }

        const saveReadLookup = () => {
            try {
                localStorage.setItem(storageKey, JSON.stringify(readLookup));
            } catch (error) {
            }
        };

        const escapeHtml = (value) => {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const formatAgo = (dateString) => {
            if (!dateString) {
                return 'Live';
            }

            const date = new Date(dateString);
            if (Number.isNaN(date.getTime())) {
                return '-';
            }

            const diffSeconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
            if (diffSeconds < 15) return 'Just now';
            if (diffSeconds < 60) return `${diffSeconds} second${diffSeconds === 1 ? '' : 's'} ago`;

            const diffMinutes = Math.floor(diffSeconds / 60);
            if (diffMinutes < 60) return `${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`;

            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours < 24) return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;

            const diffDays = Math.floor(diffHours / 24);
            if (diffDays < 30) return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;

            const diffMonths = Math.floor(diffDays / 30);
            if (diffMonths < 12) return `${diffMonths} month${diffMonths === 1 ? '' : 's'} ago`;

            const diffYears = Math.floor(diffMonths / 12);
            return `${diffYears} year${diffYears === 1 ? '' : 's'} ago`;
        };

        const updateActiveButton = () => {
            filterButtons.forEach((button) => {
                const isActive = button.getAttribute('data-filter') === activeFilter;
                button.classList.toggle('bg-emerald-50', isActive);
                button.classList.toggle('text-emerald-700', isActive);
                button.classList.toggle('font-semibold', isActive);
                button.classList.toggle('text-slate-600', !isActive);
            });
        };

        const normalizeCategory = (value) => {
            const raw = String(value ?? '').trim().toLowerCase();
            if (['approvals', 'leave', 'hiring', 'requests', 'workforce'].includes(raw)) {
                return raw;
            }
            return 'other';
        };

        const renderBadges = () => {
            const unreadCount = items.reduce((count, item) => {
                const id = item?.id ? String(item.id) : '';
                return count + (id && !readLookup[id] ? 1 : 0);
            }, 0);

            try {
                localStorage.setItem(unreadKey, String(unreadCount));
                localStorage.setItem(totalKey, String(items.length));
            } catch (error) {
            }

            document.querySelectorAll('[data-admin-notification-badge]').forEach((badge) => {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
        };

        const renderStats = (stats) => {
            const safeStats = {
                total: Number.parseInt(stats?.total ?? items.length, 10) || 0,
                approvals: Number.parseInt(stats?.approvals ?? 0, 10) || 0,
                leave: Number.parseInt(stats?.leave ?? 0, 10) || 0,
                hiring: Number.parseInt(stats?.hiring ?? 0, 10) || 0,
                requests: Number.parseInt(stats?.requests ?? 0, 10) || 0,
                workforce: Number.parseInt(stats?.workforce ?? 0, 10) || 0,
            };

            Object.entries(statElements).forEach(([key, nodes]) => {
                const value = safeStats[key] ?? 0;
                nodes.forEach((node) => {
                    node.textContent = String(value);
                });
            });
        };

        const renderRows = () => {
            const visibleItems = items.filter((item) => activeFilter === 'all' || item.filter === activeFilter);
            resultsLabel.textContent = `${visibleItems.length} conversation${visibleItems.length === 1 ? '' : 's'}`;
            updateActiveButton();

            if (!items.length) {
                listContainer.querySelectorAll('.notification-row').forEach((row) => row.remove());
                if (defaultEmpty) {
                    defaultEmpty.classList.remove('hidden');
                }
                if (filterEmpty) {
                    filterEmpty.classList.add('hidden');
                }
                return;
            }

            if (!visibleItems.length) {
                listContainer.querySelectorAll('.notification-row').forEach((row) => row.remove());
                if (defaultEmpty) {
                    defaultEmpty.classList.add('hidden');
                }
                if (filterEmpty) {
                    filterEmpty.classList.remove('hidden');
                }
                return;
            }

            if (defaultEmpty) {
                defaultEmpty.classList.add('hidden');
            }
            if (filterEmpty) {
                filterEmpty.classList.add('hidden');
            }

            const rowsHtml = visibleItems.map((item) => {
                const id = String(item?.id ?? '');
                const isRead = id !== '' && Boolean(readLookup[id]);
                const tone = toneClasses[item?.tone] || toneClasses.slate;
                const icon = iconClasses[item?.filter] || iconClasses.other;
                const rowClasses = [
                    'notification-row group grid grid-cols-1 gap-3 px-5 py-4 transition md:grid-cols-[52px_170px_minmax(0,1fr)_110px] md:items-center',
                    isRead ? 'bg-white opacity-90 hover:bg-slate-50' : 'border-l-4 border-emerald-400 bg-emerald-50 hover:bg-emerald-100',
                ].join(' ');

                return `
                    <a href="${escapeHtml(item?.href || '#')}" data-category="${escapeHtml(item?.filter || 'other')}" data-notification-id="${escapeHtml(id)}" class="${rowClasses}">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl ${tone}">
                            <i class="fa-solid ${icon}"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">${escapeHtml(item?.category || 'Update')}</p>
                            <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold ${tone}">${escapeHtml(item?.badge || 'Notice')}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="notification-title truncate text-sm ${isRead ? 'font-semibold text-slate-700' : 'font-black text-slate-900 group-hover:text-emerald-700'}">${escapeHtml(item?.title || 'Notification')}</span>
                                <span class="hidden text-slate-300 md:inline">-</span>
                                <span class="truncate text-sm text-slate-500">${escapeHtml(item?.message || '')}</span>
                            </div>
                        </div>
                        <div class="text-sm font-medium text-slate-400 md:text-right">${escapeHtml(item?.date_human || formatAgo(item?.date || null))}</div>
                    </a>
                `;
            }).join('');

            listContainer.querySelectorAll('.notification-row').forEach((row) => row.remove());
            const anchor = filterEmpty || defaultEmpty;
            if (anchor) {
                anchor.insertAdjacentHTML('beforebegin', rowsHtml);
            } else {
                listContainer.insertAdjacentHTML('beforeend', rowsHtml);
            }
        };

        const setItems = (nextItems) => {
            items = (Array.isArray(nextItems) ? nextItems : []).map((item) => {
                const category = normalizeCategory(item?.filter ?? item?.category);
                const categoryLabelMap = {
                    approvals: 'Approvals',
                    leave: 'Leave',
                    hiring: 'Hiring',
                    requests: 'Requests',
                    workforce: 'Workforce',
                    other: 'Update',
                };

                return {
                    id: item?.id ? String(item.id) : '',
                    category: String(item?.category || categoryLabelMap[category] || 'Update'),
                    filter: category,
                    title: String(item?.title || 'Notification'),
                    message: String(item?.message || ''),
                    href: String(item?.href || '#'),
                    badge: String(item?.badge || 'Notice'),
                    tone: String(item?.tone || 'slate'),
                    date: item?.date || null,
                    date_human: String(item?.date_human || ''),
                };
            });
        };

        filterButtons.forEach((button) => {
            button.addEventListener('click', function () {
                activeFilter = this.getAttribute('data-filter') || 'all';
                renderRows();
            });
        });

        listContainer.addEventListener('click', (event) => {
            const row = event.target.closest('.notification-row[data-notification-id]');
            if (!row) {
                return;
            }

            const id = row.getAttribute('data-notification-id') || '';
            if (!id) {
                return;
            }

            readLookup[id] = true;
            saveReadLookup();
            renderRows();
            renderBadges();
        });

        const syncFromServer = async () => {
            try {
                const response = await fetch(summaryUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Unable to fetch notification summary.');
                }

                const payload = await response.json();
                setItems(payload?.items ?? []);
                renderStats(payload?.stats ?? {});
                renderRows();
                renderBadges();
            } catch (error) {
                renderRows();
                renderBadges();
            }
        };

        setItems(initialItems);
        renderStats(initialStats);
        renderRows();
        renderBadges();
        syncFromServer();
        window.setInterval(syncFromServer, 30000);
    })();
</script>
</body>
</html>
