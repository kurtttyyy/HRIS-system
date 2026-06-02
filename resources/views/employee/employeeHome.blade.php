<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { transition: margin-left 0.3s ease; }
        main { transition: margin-left 0.3s ease; }
        aside:not(:hover) ~ main { margin-left: 4rem; }
        aside:hover ~ main { margin-left: 14rem; }
        .employee-dashboard-reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.28s ease, transform 0.28s ease;
            will-change: opacity, transform;
        }
        .employee-dashboard-reveal.reveal-from-top {
            transform: translateY(-18px);
        }
        .employee-dashboard-reveal.is-visible {
            animation: employee-dashboard-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
            animation-delay: var(--employee-dashboard-delay, 0ms);
        }
        .employee-dashboard-card-motion {
            transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
        }
        .employee-dashboard-card-motion:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }
        .employee-dashboard-icon-pop {
            animation: employee-dashboard-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
            animation-delay: var(--employee-dashboard-delay, 0ms);
        }
        .employee-dashboard-progress-fill {
            transform-origin: left center;
            transform: scaleX(0);
            transition: transform 0.28s ease;
            will-change: transform;
        }
        .employee-dashboard-progress-fill.is-visible {
            animation: employee-dashboard-progress-grow 0.5s cubic-bezier(0.22, 0.9, 0.2, 1) both;
            animation-delay: var(--employee-dashboard-delay, 90ms);
        }
        @keyframes employee-dashboard-fade-up {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes employee-dashboard-pop-in {
            0% {
                opacity: 0;
                transform: scale(0.82) rotate(-4deg);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }
        @keyframes employee-dashboard-progress-grow {
            from { transform: scaleX(0); }
            to { transform: scaleX(1); }
        }
        @media (prefers-reduced-motion: reduce) {
            .employee-dashboard-reveal,
            .employee-dashboard-icon-pop,
            .employee-dashboard-progress-fill {
                animation: none;
                opacity: 1;
                transform: none;
            }
            .employee-dashboard-card-motion {
                transition: none;
            }
            .employee-dashboard-card-motion:hover {
                transform: none;
            }
        }
    </style>
</head>
<body class="bg-slate-100">
@php
    $welcomeUser = auth()->user();
    $welcomeName = trim((string) ($welcomeUser?->first_name ?? ''));
    if ($welcomeName === '') {
        $welcomeName = 'Staff';
    }
    $showEmployeeWelcome = (bool) session('show_employee_welcome');
    $staffGuideUrl = route('employee.employeeStaffGuide', array_filter([
        'tab_session' => request()->query('tab_session'),
    ]));
@endphp

<div class="flex min-h-screen">
    @include('components.employeeSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        @include('components.employeeHeader.dashboardHeader', [
            'notifications' => (int) ($notifications ?? 0),
        ])

        <div id="employee-dashboard-page" class="space-y-8 p-4 pt-20 md:p-8">
            <section class="employee-dashboard-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/40 bg-gradient-to-br from-slate-950 via-emerald-950 to-emerald-800 p-6 text-white shadow-2xl md:p-8" style="--employee-dashboard-delay: 0ms;">
                <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute bottom-0 right-20 h-24 w-24 rounded-full bg-emerald-300/10 blur-2xl"></div>
                <div class="relative grid gap-6 lg:grid-cols-[1.7fr_1fr] lg:items-start">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">
                            Employee Workspace
                        </div>
                        <div>
                            <h1 class="max-w-2xl text-3xl font-black leading-tight md:text-5xl">Your workday at a glance.</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-50 md:text-base">
                                Track attendance, monitor leave balances, and jump into the tasks you use most without digging through menus.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3 text-sm">
                            <div class="employee-dashboard-card-motion employee-dashboard-reveal rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm" style="--employee-dashboard-delay: 50ms;">
                                <p class="text-xs uppercase tracking-wide text-emerald-100">Month</p>
                                <p class="mt-1 font-semibold">{{ now()->format('F Y') }}</p>
                            </div>
                            <div class="employee-dashboard-card-motion employee-dashboard-reveal rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm" style="--employee-dashboard-delay: 80ms;">
                                <p class="text-xs uppercase tracking-wide text-emerald-100">Attendance</p>
                                <p class="mt-1 font-semibold">{{ $attendanceStatusLabel ?? 'No Data' }}</p>
                            </div>
                            <div class="employee-dashboard-card-motion employee-dashboard-reveal rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm" style="--employee-dashboard-delay: 110ms;">
                                <p class="text-xs uppercase tracking-wide text-emerald-100">Available Leave</p>
                                <p class="mt-1 font-semibold">{{ rtrim(rtrim(number_format((float) ($combinedLeaveAvailable ?? 0), 1, '.', ''), '0'), '.') }} Days</p>
                            </div>
                        </div>

                    </div>

                    <div class="space-y-4">
                        <div class="employee-dashboard-card-motion employee-dashboard-reveal rounded-[1.75rem] border border-white/15 bg-white/10 p-5 backdrop-blur-sm" style="--employee-dashboard-delay: 90ms;">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Focus Panel</p>
                            <div class="mt-5 space-y-4">
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-emerald-50">Leave usage</span>
                                        <span class="font-semibold">{{ (int) ($combinedLeavePercentUsed ?? 0) }}%</span>
                                    </div>
                                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/15">
                                        <div class="employee-dashboard-progress-fill h-full rounded-full bg-emerald-300" style="width: {{ (int) ($combinedLeavePercentUsed ?? 0) }}%; --employee-dashboard-delay: 140ms;"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="employee-dashboard-card-motion rounded-2xl bg-white/10 p-4">
                                        <p class="text-xs uppercase tracking-wide text-emerald-100">Vacation</p>
                                        <p class="mt-2 text-xl font-bold">{{ rtrim(rtrim(number_format((float) ($vacationCardAvailable ?? 0), 1, '.', ''), '0'), '.') }}</p>
                                    </div>
                                    <div class="employee-dashboard-card-motion rounded-2xl bg-white/10 p-4">
                                        <p class="text-xs uppercase tracking-wide text-emerald-100">Sick</p>
                                        <p class="mt-2 text-xl font-bold">{{ rtrim(rtrim(number_format((float) ($sickCardAvailable ?? 0), 1, '.', ''), '0'), '.') }}</p>
                                    </div>
                                </div>
                                <p class="text-xs leading-5 text-emerald-50">
                                    Keep your records updated to avoid delays in payroll, leave approvals, and required document submissions.
                                </p>
                            </div>
                        </div>

                        <div class="employee-dashboard-card-motion employee-dashboard-reveal rounded-[1.5rem] border border-white/15 bg-white/10 p-5 backdrop-blur-sm" style="--employee-dashboard-delay: 130ms;">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Quick Actions</p>
                                <span class="text-[11px] text-emerald-50/85">Most-used tools</span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
                                <a href="{{ route('employee.employeeLeave') }}" class="group flex min-h-[3rem] items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/20">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-50 transition group-hover:bg-white/20">
                                        <i class="fa fa-calendar-check-o"></i>
                                    </span>
                                    <span class="truncate">Leave</span>
                                </a>
                                <a href="{{ route('employee.employeeDocument') }}" class="group flex min-h-[3rem] items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/20">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-50 transition group-hover:bg-white/20">
                                        <i class="fa fa-folder-open"></i>
                                    </span>
                                    <span class="truncate">Documents</span>
                                </a>
                                <a href="{{ route('employee.employeePayslip') }}" class="group flex min-h-[3rem] items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/20">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-50 transition group-hover:bg-white/20">
                                        <i class="fa fa-money"></i>
                                    </span>
                                    <span class="truncate">Payslip</span>
                                </a>
                                <a href="{{ route('employee.employeeCommunication') }}" class="group flex min-h-[3rem] items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/20">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-50 transition group-hover:bg-white/20">
                                        <i class="fa fa-users"></i>
                                    </span>
                                    <span class="truncate">Directory</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if (!empty($accountAlerts ?? []))
                <section class="employee-dashboard-reveal rounded-[1.75rem] border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm md:p-6" style="--employee-dashboard-delay: 150ms;">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Account Alerts</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900">Action needed on your account</h3>
                            <p class="mt-1 text-sm text-slate-600">Review these updates to keep your account records complete and up to date.</p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-3 py-1.5 text-xs font-semibold text-rose-700">
                            <i class="fa fa-exclamation-circle"></i>
                            {{ count($accountAlerts) }} item{{ count($accountAlerts) === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach ($accountAlerts as $alert)
                            @php
                                $tone = strtolower(trim((string) ($alert['tone'] ?? 'slate')));
                                $alertToneClass = match ($tone) {
                                    'rose' => 'border-rose-200 bg-rose-50',
                                    'amber' => 'border-amber-200 bg-amber-50',
                                    'violet' => 'border-violet-200 bg-violet-50',
                                    'sky' => 'border-sky-200 bg-sky-50',
                                    default => 'border-slate-200 bg-slate-50',
                                };
                            @endphp
                            <a href="{{ $alert['href'] ?? '#' }}" class="employee-dashboard-card-motion block rounded-2xl border px-4 py-3 {{ $alertToneClass }}">
                                <p class="text-sm font-bold text-slate-900">{{ $alert['title'] ?? 'Account update' }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ $alert['desc'] ?? '' }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <article class="employee-dashboard-card-motion employee-dashboard-reveal rounded-[1.75rem] border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm" style="--employee-dashboard-delay: 170ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div class="employee-dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-lg shadow-blue-500/25" style="--employee-dashboard-delay: 200ms;">
                            <i class="fa fa-calendar fa-2x"></i>
                        </div>
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">{{ (int) ($combinedLeavePercentUsed ?? 0) }}% Used</span>
                    </div>
                    <h3 class="mt-8 text-4xl font-black text-slate-900">{{ rtrim(rtrim(number_format((float) ($combinedLeaveAvailable ?? 0), 1, '.', ''), '0'), '.') }}</h3>
                    <p class="mt-1 text-sm font-medium text-slate-600">Leave Balance</p>
                    <div class="mt-5 space-y-2 text-xs text-slate-500">
                        <p>Vacation: {{ rtrim(rtrim(number_format((float) ($vacationCardAvailable ?? 0), 1, '.', ''), '0'), '.') }} / {{ rtrim(rtrim(number_format((float) ($annualLimit ?? 0), 1, '.', ''), '0'), '.') }}</p>
                        <p>Sick: {{ rtrim(rtrim(number_format((float) ($sickCardAvailable ?? 0), 1, '.', ''), '0'), '.') }} / {{ rtrim(rtrim(number_format((float) ($sickLimit ?? 0), 1, '.', ''), '0'), '.') }}</p>
                    </div>
                    <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-blue-100">
                        <div class="employee-dashboard-progress-fill h-full rounded-full bg-blue-500" style="width: {{ (int) ($combinedLeavePercentUsed ?? 0) }}%; --employee-dashboard-delay: 230ms;"></div>
                    </div>
                </article>

                <article class="employee-dashboard-card-motion employee-dashboard-reveal rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm" style="--employee-dashboard-delay: 200ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div class="employee-dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/25" style="--employee-dashboard-delay: 230ms;">
                            <i class="fa fa-clock-o fa-2x"></i>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $attendanceStatusLabel ?? 'No Data' }}</span>
                    </div>
                    <h3 class="mt-8 text-4xl font-black text-slate-900">{{ number_format((float) ($attendanceRatePercent ?? 0), 1) }}%</h3>
                    <p class="mt-1 text-sm font-medium text-slate-600">Attendance Rate</p>
                    <p class="mt-5 text-xs leading-5 text-slate-500">{{ $attendanceMonthLabel ?? now()->format('F Y') }} • {{ (int) ($attendancePresentDays ?? 0) }}/{{ (int) ($attendanceTotalDays ?? 0) }} days present</p>
                </article>

                <article class="employee-dashboard-card-motion employee-dashboard-reveal rounded-[1.75rem] border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-6 shadow-sm" style="--employee-dashboard-delay: 230ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div class="employee-dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg shadow-violet-500/25" style="--employee-dashboard-delay: 260ms;">
                            <i class="fa fa-calendar-o fa-2x"></i>
                        </div>
                        <span id="month-events-badge" class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">0 Events</span>
                    </div>
                    <h3 class="mt-8 text-3xl font-black text-slate-900">Monthly Pulse</h3>
                    <p class="mt-1 text-sm font-medium text-slate-600">Upcoming Events</p>
                    <p id="month-events-caption" class="mt-5 text-xs leading-5 text-slate-500">{{ now()->format('F Y') }}</p>
                </article>

                <article class="employee-dashboard-card-motion employee-dashboard-reveal rounded-[1.75rem] border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm" style="--employee-dashboard-delay: 260ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div class="employee-dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/25" style="--employee-dashboard-delay: 290ms;">
                            <i class="fa fa-credit-card fa-2x"></i>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Salary</span>
                    </div>
                    <h3 class="mt-8 text-3xl font-black text-slate-900">Payment Hub</h3>
                    <p class="mt-1 text-sm font-medium text-slate-600">Payslip and salary access</p>
                    <p class="mt-5 text-xs leading-5 text-slate-500">Use the payslip section to review your latest payroll records.</p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div id="weekly-attendance-section" class="employee-dashboard-reveal bg-white rounded-2xl border border-gray-200 p-4 md:p-6" style="--employee-dashboard-delay: 300ms;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">This Week's Attendance</h3>
                        <span class="text-sm text-gray-500 font-medium">{{ $weeklyAttendanceRangeLabel ?? '-' }}</span>
                    </div>

                    <div class="space-y-6 max-h-96 overflow-y-auto pr-2">
                        @forelse (($weeklyAttendanceRows ?? []) as $weeklyRow)
                            <div>
                                <div class="grid grid-cols-12 items-start gap-4">
                                    <div class="col-span-2 mt-5 ml-8 relative w-12 h-12 text-green-600">
                                        <i class="fa fa-calendar-o fa-4x w-full h-full mt-[0px]"></i>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center text-xs font-bold mt-8 ml-2">
                                            <span>{{ $weeklyRow['day_short'] ?? '-' }}</span>
                                            <span>{{ $weeklyRow['day_number'] ?? '-' }}</span>
                                        </div>
                                    </div>

                                    <div class="col-span-10 bg-gray-50 border border-gray-200 rounded-xl p-4 mr-2">
                                        <div class="flex items-center justify-between mb-3">
                                            <p class="text-xs text-gray-500">{{ $weeklyRow['date_label'] ?? '-' }}</p>
                                            <span class="px-2 py-1 rounded-lg text-xs font-medium {{ $weeklyRow['status_class'] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $weeklyRow['status'] ?? 'No Data' }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-12 items-center gap-4">
                                            <div class="col-span-6 space-y-2">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                                    <p class="text-sm text-gray-700">{{ $weeklyRow['morning_range'] ?? 'No Log' }}</p>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                                    <p class="text-sm text-gray-700">{{ $weeklyRow['afternoon_range'] ?? 'No Log' }}</p>
                                                </div>
                                            </div>
                                            
                                            <div class="col-span-6 space-y-2 text-right">
                                                <p class="text-xs text-gray-500">{{ $weeklyRow['morning_hours'] ?? '0 hrs worked' }}</p>
                                                <p class="text-xs text-gray-500">{{ $weeklyRow['afternoon_hours'] ?? '0 hrs worked' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No attendance records for this week.</p>
                        @endforelse
                    </div>
                </div>

                <div class="employee-dashboard-reveal rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm md:p-6" style="--employee-dashboard-delay: 340ms;">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-700">Events</p>
                            <h3 class="mt-1 text-xl font-black text-slate-900">Upcoming Events</h3>
                        </div>
                        <span id="upcoming-events-range" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">{{ now()->format('F Y') }}</span>
                    </div>

                    <ul id="upcoming-events-list" class="space-y-4 max-h-96 overflow-y-auto pr-2">
                        <li class="text-sm text-gray-500">Loading events...</li>
                    </ul>
                </div>
            </section>
        </div>
    </main>
</div>

@if ($showEmployeeWelcome)
    <div
        id="employeeWelcomeModal"
        class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-md"
        data-employee-welcome-modal
        data-dismiss-key="employee_welcome_seen_{{ (int) ($welcomeUser?->id ?? 0) }}"
    >
        <div class="w-full max-w-[40rem] overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-slate-950/30">
            <div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-emerald-950 to-emerald-800 px-6 pb-9 pt-7 text-white md:px-8">
                <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-emerald-300/10 blur-3xl"></div>
                <div class="relative flex flex-col items-center text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[1.25rem] border border-white/20 bg-white/10 p-2.5 shadow-lg shadow-slate-950/25 backdrop-blur-sm">
                        <img src="{{ asset('images/logo.webp') }}" alt="Northeastern College" class="h-full w-full object-contain">
                    </div>
                    <p class="mt-5 text-[11px] font-black uppercase tracking-[0.32em] text-emerald-100">Employee Workspace</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight md:text-4xl">Welcome aboard, {{ $welcomeName }}!</h2>
                </div>
            </div>

            <div class="px-6 py-6 md:px-8">
                <p class="mx-auto max-w-2xl text-sm leading-6 text-slate-600">
                    Start with the <span class="font-bold text-emerald-800">Staff Guide</span> to understand how the HRIS works, how to check your records, submit documents, file leave, review payslips, and follow HR updates.
                </p>

                <div class="mt-5 rounded-[1.5rem] border border-emerald-200 bg-emerald-50/80 p-3.5 shadow-inner shadow-emerald-900/5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('employee.employeeHome') }}" class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-white shadow-lg shadow-emerald-700/20"><i class="fa fa-dashboard"></i></span>
                            <span>
                                Dashboard
                                <span class="block text-xs font-medium text-slate-500">Attendance, leave, alerts</span>
                            </span>
                        </a>
                        <a href="{{ route('employee.employeeDocument') }}" class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-lg shadow-sky-600/20"><i class="fa fa-folder-open"></i></span>
                            <span>
                                Documents
                                <span class="block text-xs font-medium text-slate-500">201 file requirements</span>
                            </span>
                        </a>
                        <a href="{{ route('employee.employeeLeave') }}" class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white shadow-lg shadow-amber-600/20"><i class="fa fa-calendar-check-o"></i></span>
                            <span>
                                Leave Requests
                                <span class="block text-xs font-medium text-slate-500">File and track status</span>
                            </span>
                        </a>
                        <a href="{{ route('employee.employeePayslip') }}" class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20"><i class="fa fa-file-text-o"></i></span>
                            <span>
                                Payslips
                                <span class="block text-xs font-medium text-slate-500">Payroll records</span>
                            </span>
                        </a>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl px-5 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-50"
                        data-employee-welcome-dismiss
                    >
                        Maybe later
                    </button>
                    <a
                        href="{{ $staffGuideUrl }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-700/25 transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-xl"
                        data-employee-welcome-guide
                    >
                        <i class="fa fa-book"></i>
                        Open Staff Guide
                    </a>
                </div>

                <p class="mt-6 flex items-start gap-2 text-sm font-medium text-rose-600">
                    <i class="fa fa-info-circle mt-0.5"></i>
                    <span>This message will not appear again after you dismiss it or open the Staff Guide.</span>
                </p>
            </div>
        </div>
    </div>
@endif

<script>
    const initEmployeeDashboardAnimation = () => {
        const page = document.getElementById('employee-dashboard-page');
        if (!page) return;

        const revealItems = Array.from(page.querySelectorAll('.employee-dashboard-reveal, .employee-dashboard-progress-fill'));
        if (!revealItems.length) return;

        if (!('IntersectionObserver' in window)) {
            revealItems.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        let lastScrollY = window.scrollY;
        let scrollDirection = 'down';

        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            scrollDirection = currentScrollY < lastScrollY ? 'up' : 'down';
            lastScrollY = currentScrollY;
        }, { passive: true });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.toggle('reveal-from-top', scrollDirection === 'up');
                    entry.target.classList.add('is-visible');
                    return;
                }

                entry.target.classList.remove('is-visible');
            });
        }, {
            root: null,
            threshold: 0.12,
            rootMargin: '-8% 0px -8% 0px',
        });

        revealItems.forEach((item) => observer.observe(item));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeDashboardAnimation, { once: true });
    } else {
        initEmployeeDashboardAnimation();
    }

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
        const modal = document.querySelector('[data-employee-welcome-modal]');
        if (!modal) return;

        const dismissKey = modal.getAttribute('data-dismiss-key') || 'employee_welcome_seen';
        const dismiss = () => {
            try {
                localStorage.setItem(dismissKey, '1');
            } catch (error) {
            }
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        try {
            if (localStorage.getItem(dismissKey) === '1') {
                return;
            }
        } catch (error) {
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        document.querySelectorAll('[data-employee-welcome-dismiss]').forEach((button) => {
            button.addEventListener('click', dismiss);
        });

        document.querySelectorAll('[data-employee-welcome-guide]').forEach((link) => {
            link.addEventListener('click', () => {
                try {
                    localStorage.setItem(dismissKey, '1');
                } catch (error) {
                }
            });
        });
    })();

    (function () {
        const focusId = @json(request()->query('focus'));
        if (!focusId) return;
        const target = document.getElementById(focusId);
        if (!target) return;

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('ring-4', 'ring-emerald-300', 'ring-offset-4', 'ring-offset-slate-100', 'transition');

        setTimeout(() => {
            target.classList.remove('ring-4', 'ring-emerald-300', 'ring-offset-4', 'ring-offset-slate-100');
        }, 2200);
    })();

    (function updateThisMonthEventsCard() {
        const badgeEl = document.getElementById('month-events-badge');
        const captionEl = document.getElementById('month-events-caption');
        if (!badgeEl || !captionEl) return;

        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        const monthLabel = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        captionEl.textContent = monthLabel;

        const readJson = (key) => {
            try {
                const raw = localStorage.getItem(key);
                if (!raw) return {};
                const parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        };

        const customEvents = readJson('school_custom_events_v1');
        const recurringEvents = readJson('school_recurring_events_v1');
        const recurringExamDays = readJson('school_recurring_exams_v1');
        const customHolidays = readJson('school_custom_holidays_v1');
        const recurringHolidays = readJson('school_recurring_holidays_v1');
        const hiddenSpecialEvents = readJson('hidden_special_events_v1');
        const hiddenOfficialHolidays = readJson('hidden_official_holidays_v1');

        const specialEventsByDate = {};
        const addSpecial = (date, label) => {
            if (!specialEventsByDate[date]) specialEventsByDate[date] = [];
            specialEventsByDate[date].push(label);
        };
        addSpecial(`${year}-02-14`, "Valentine's Day");
        addSpecial(`${year}-03-08`, "International Women's Day");
        addSpecial(`${year}-04-22`, 'Earth Day');
        addSpecial(`${year}-10-31`, 'Halloween');
        addSpecial(`${year}-11-01`, "All Saints' Day");
        addSpecial(`${year}-11-02`, "All Souls' Day");
        addSpecial(`${year}-12-24`, 'Christmas Eve');
        addSpecial(`${year}-12-31`, "New Year's Eve");
        const chineseNewYearByYear = {
            2024: '2024-02-10',
            2025: '2025-01-29',
            2026: '2026-02-17',
            2027: '2027-02-06',
            2028: '2028-01-26',
            2029: '2029-02-13',
            2030: '2030-02-03',
            2031: '2031-01-23',
            2032: '2032-02-11',
            2033: '2033-01-31',
            2034: '2034-02-19',
            2035: '2035-02-08',
        };
        if (chineseNewYearByYear[year]) {
            addSpecial(chineseNewYearByYear[year], 'Chinese New Year');
        }

        const dateInCurrentMonth = (dateText) => {
            const parsed = new Date(`${dateText}T00:00:00`);
            return !Number.isNaN(parsed.getTime()) && parsed.getFullYear() === year && parsed.getMonth() === month;
        };

        let totalEvents = 0;

        Object.entries(customEvents).forEach(([dateText, values]) => {
            if (dateInCurrentMonth(dateText) && Array.isArray(values)) totalEvents += values.length;
        });

        Object.entries(customHolidays).forEach(([dateText, values]) => {
            if (dateInCurrentMonth(dateText) && Array.isArray(values)) totalEvents += values.length;
        });

        Object.entries(specialEventsByDate).forEach(([dateText, values]) => {
            if (!dateInCurrentMonth(dateText) || !Array.isArray(values)) return;
            const hidden = Array.isArray(hiddenSpecialEvents[dateText]) ? hiddenSpecialEvents[dateText] : [];
            totalEvents += values.filter((name) => !hidden.includes(name)).length;
        });

        const monthDayPrefix = `${String(month + 1).padStart(2, '0')}-`;
        Object.entries(recurringEvents).forEach(([monthDay, values]) => {
            if (monthDay.startsWith(monthDayPrefix) && Array.isArray(values)) totalEvents += values.length;
        });
        Object.entries(recurringExamDays).forEach(([monthDay, values]) => {
            if (monthDay.startsWith(monthDayPrefix) && Array.isArray(values)) totalEvents += values.length;
        });
        Object.entries(recurringHolidays).forEach(([monthDay, values]) => {
            if (monthDay.startsWith(monthDayPrefix) && Array.isArray(values)) totalEvents += values.length;
        });

        fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/US`)
            .then((response) => (response.ok ? response.json() : []))
            .then((holidays) => {
                if (Array.isArray(holidays)) {
                    holidays.forEach((holiday) => {
                        const dateText = holiday?.date;
                        if (!dateText || !dateInCurrentMonth(dateText)) return;
                        const holidayName = holiday?.localName || holiday?.name || 'Holiday';
                        const hidden = Array.isArray(hiddenOfficialHolidays[dateText]) ? hiddenOfficialHolidays[dateText] : [];
                        if (!hidden.includes(holidayName)) {
                            totalEvents += 1;
                        }
                    });
                }
            })
            .catch(() => {
            })
            .finally(() => {
                badgeEl.textContent = `${totalEvents} Event${totalEvents === 1 ? '' : 's'}`;
            });
    })();

    (function updateUpcomingEventsCard() {
        const listEl = document.getElementById('upcoming-events-list');
        const rangeEl = document.getElementById('upcoming-events-range');
        if (!listEl || !rangeEl) return;

        const readJson = (key) => {
            try {
                const raw = localStorage.getItem(key);
                if (!raw) return {};
                const parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        };

        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        const todayDate = new Date(year, month, now.getDate());
        rangeEl.textContent = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        const customEvents = readJson('school_custom_events_v1');
        const recurringEvents = readJson('school_recurring_events_v1');
        const recurringExamDays = readJson('school_recurring_exams_v1');
        const customHolidays = readJson('school_custom_holidays_v1');
        const recurringHolidays = readJson('school_recurring_holidays_v1');
        const hiddenSpecialEvents = readJson('hidden_special_events_v1');
        const hiddenOfficialHolidays = readJson('hidden_official_holidays_v1');

        const parseIso = (iso) => {
            const d = new Date(`${iso}T00:00:00`);
            return Number.isNaN(d.getTime()) ? null : d;
        };

        const monthDays = new Date(year, month + 1, 0).getDate();
        const monthEvents = [];
        const pushEvent = (isoDate, title, type = 'event') => {
            const d = parseIso(isoDate);
            if (!d) return;
            if (d.getFullYear() !== year || d.getMonth() !== month) return;
            const isEnded = d < todayDate;
            monthEvents.push({ date: isoDate, title: String(title), ts: d.getTime(), isEnded, type });
        };

        Object.entries(customEvents).forEach(([date, values]) => {
            if (Array.isArray(values)) values.forEach((title) => pushEvent(date, title, 'school_event'));
        });
        Object.entries(customHolidays).forEach(([date, values]) => {
            if (Array.isArray(values)) values.forEach((title) => pushEvent(date, title, 'employee_holiday'));
        });

        for (let day = 1; day <= monthDays; day++) {
            const isoDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const monthDay = `${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const recurringList = Array.isArray(recurringEvents[monthDay]) ? recurringEvents[monthDay] : [];
            recurringList.forEach((title) => pushEvent(isoDate, title, 'school_event'));
            const examList = Array.isArray(recurringExamDays[monthDay]) ? recurringExamDays[monthDay] : [];
            examList.forEach((title) => pushEvent(isoDate, title, 'exam_day'));
            const recurringHolidayList = Array.isArray(recurringHolidays[monthDay]) ? recurringHolidays[monthDay] : [];
            recurringHolidayList.forEach((title) => pushEvent(isoDate, title, 'employee_holiday'));
        }

        const specialEventsByDate = {};
        const addSpecial = (date, label) => {
            if (!specialEventsByDate[date]) specialEventsByDate[date] = [];
            specialEventsByDate[date].push(label);
        };
        addSpecial(`${year}-02-14`, "Valentine's Day");
        addSpecial(`${year}-03-08`, "International Women's Day");
        addSpecial(`${year}-04-22`, 'Earth Day');
        addSpecial(`${year}-10-31`, 'Halloween');
        addSpecial(`${year}-11-01`, "All Saints' Day");
        addSpecial(`${year}-11-02`, "All Souls' Day");
        addSpecial(`${year}-12-24`, 'Christmas Eve');
        addSpecial(`${year}-12-31`, "New Year's Eve");
        const chineseNewYearByYear = {
            2024: '2024-02-10', 2025: '2025-01-29', 2026: '2026-02-17', 2027: '2027-02-06',
            2028: '2028-01-26', 2029: '2029-02-13', 2030: '2030-02-03', 2031: '2031-01-23',
            2032: '2032-02-11', 2033: '2033-01-31', 2034: '2034-02-19', 2035: '2035-02-08',
        };
        if (chineseNewYearByYear[year]) addSpecial(chineseNewYearByYear[year], 'Chinese New Year');

        Object.entries(specialEventsByDate).forEach(([date, values]) => {
            const hidden = Array.isArray(hiddenSpecialEvents[date]) ? hiddenSpecialEvents[date] : [];
            values.filter((name) => !hidden.includes(name)).forEach((name) => pushEvent(date, name, 'special_event'));
        });

        const render = (officialHolidayEvents) => {
            const allEvents = [...monthEvents, ...officialHolidayEvents]
                .sort((a, b) => {
                    if (a.isEnded !== b.isEnded) {
                        return a.isEnded ? 1 : -1;
                    }
                    return b.ts - a.ts || a.title.localeCompare(b.title);
                })
                .slice(0, 12);

            if (!allEvents.length) {
                listEl.innerHTML = '<li class="text-sm text-gray-500">No upcoming events this month.</li>';
                return;
            }

            listEl.innerHTML = allEvents.map((event) => {
                const eventDate = new Date(`${event.date}T00:00:00`);
                const label = eventDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const dayDiff = Math.floor((eventDate - todayDate) / (1000 * 60 * 60 * 24));
                let relativeLabel = '';
                if (dayDiff === 0) {
                    relativeLabel = 'Today';
                } else if (dayDiff === 1) {
                    relativeLabel = 'Tomorrow';
                }
                const isToday = dayDiff === 0;
                const statusText = event.isEnded ? 'Ended' : (isToday ? 'Ongoing' : 'Upcoming');
                const statusClass = event.isEnded
                    ? 'bg-slate-100 text-slate-600'
                    : (isToday ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700');
                const iconClassByType = {
                    employee_holiday: 'bg-rose-100 text-rose-500 border-rose-500',
                    official_holiday: 'bg-rose-100 text-rose-500 border-rose-500',
                    special_event: 'bg-red-100 text-red-500 border-red-500',
                    school_event: 'bg-yellow-100 text-yellow-600 border-yellow-500',
                    exam_day: 'bg-emerald-100 text-emerald-600 border-emerald-500',
                };
                const iconClasses = iconClassByType[event.type] || 'bg-orange-100 text-orange-500 border-orange-500';
                return `<li class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
                    <span class="w-10 h-10 ${iconClasses} flex items-center justify-center rounded-xl border">
                        <i class="fa fa-calendar"></i>
                    </span>
                    <div class="flex flex-col flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold text-gray-900">${event.title}</p>
                            <span class="px-2 py-0.5 rounded text-xs font-medium ${statusClass}">${statusText}</span>
                        </div>
                        <p class="text-sm text-gray-600">${label}${relativeLabel ? ` • ${relativeLabel}` : ''}</p>
                    </div>
                </li>`;
            }).join('');
        };

        fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/US`)
            .then((response) => (response.ok ? response.json() : []))
            .then((holidays) => {
                const officialHolidayEvents = [];
                if (Array.isArray(holidays)) {
                    holidays.forEach((holiday) => {
                        const dateText = holiday?.date;
                        if (!dateText) return;
                        const d = parseIso(dateText);
                        if (!d || d.getFullYear() !== year || d.getMonth() !== month) return;
                        const name = holiday?.localName || holiday?.name || 'Holiday';
                        const hidden = Array.isArray(hiddenOfficialHolidays[dateText]) ? hiddenOfficialHolidays[dateText] : [];
                        if (!hidden.includes(name)) {
                            officialHolidayEvents.push({
                                date: dateText,
                                title: String(name),
                                ts: d.getTime(),
                                isEnded: d < todayDate,
                                type: 'official_holiday',
                            });
                        }
                    });
                }
                render(officialHolidayEvents);
            })
            .catch(() => {
                render([]);
            });
    })();
</script>

</body>
</html>
