<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            transition: margin-left 0.3s ease;
        }
        
        main {
            transition: margin-left 0.3s ease;
        }
        
        aside:not(:hover) ~ main {
            margin-left: 4rem;
        }
        
        aside:hover ~ main {
            margin-left: 14rem;
        }

        #employee-leave-page .employee-leave-reveal {
            opacity: 0;
            transform: translateY(24px);
            transition:
                opacity 0.7s ease,
                transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-leave-delay, 0ms);
        }

        #employee-leave-page .employee-leave-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        #employee-leave-page .employee-leave-card-motion {
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }

        #employee-leave-page .employee-leave-card-motion:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        #employee-leave-page .employee-leave-icon-pop {
            opacity: 0;
            transform: scale(0.86) rotate(-4deg);
            transition:
                opacity 0.55s ease,
                transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-leave-delay, 120ms);
        }

        #employee-leave-page .is-visible .employee-leave-icon-pop,
        #employee-leave-page .employee-leave-icon-pop.is-visible {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }

        #employee-leave-page .employee-leave-progress-fill {
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.85s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-leave-delay, 180ms);
        }

        #employee-leave-page .employee-leave-progress-fill.is-visible {
            transform: scaleX(1);
        }

        @media (prefers-reduced-motion: reduce) {
            #employee-leave-page .employee-leave-reveal,
            #employee-leave-page .employee-leave-icon-pop,
            #employee-leave-page .employee-leave-progress-fill,
            #employee-leave-page .employee-leave-card-motion {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>
<body class="bg-slate-100">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    @include('components.employeeSideBar')

    <!-- Main Content -->
    <main class="flex-1 ml-16 transition-all duration-300">
<div id="employee-leave-page" class="p-4 md:p-8 space-y-8 pt-4">
    @php
        $authUser = auth()->user();
        $activeEmployeeForm = request()->query('form', 'leave');
        if (!in_array($activeEmployeeForm, ['leave', 'official'], true)) {
            $activeEmployeeForm = 'leave';
        }
        $employeeFormName = $employeeDisplayName
            ?? trim(implode(' ', array_filter([
                $authUser?->first_name ?? null,
                $authUser?->middle_name ?? null,
                $authUser?->last_name ?? null,
            ])));
        $employeeFormPosition = $authUser?->employee?->position
            ?? data_get($authUser, 'applicant.position.title')
            ?? '';
        $employeeFormQueryBase = array_filter([
            'month' => $selectedMonth ?? now()->format('Y-m'),
        ], fn ($value) => !is_null($value) && $value !== '');
        $monthRecords = collect($monthRequestRecords ?? []);
        $approvedCount = $monthRecords->filter(fn ($record) => strcasecmp((string) ($record['status'] ?? ''), 'Approved') === 0)->count();
        $pendingCount = $monthRecords->filter(fn ($record) => strcasecmp((string) ($record['status'] ?? ''), 'Pending') === 0)->count();
        $rejectedCount = $monthRecords->filter(fn ($record) => strcasecmp((string) ($record['status'] ?? ''), 'Rejected') === 0)->count();
        $totalRequestCount = $monthRecords->count();
        $vacationAvailable = (float) ($vacationCardAvailable ?? max(($annualLimit ?? 0) - ($annualUsed ?? 0), 0));
        $vacationLimit = max((float) ($annualLimit ?? 0), 0.0);
        $vacationUsed = (float) ($annualUsed ?? 0);
        $vacationPercentUsed = $vacationLimit > 0 ? min(($vacationUsed / $vacationLimit) * 100, 100) : 0;
        $sickAvailable = (float) ($sickCardAvailable ?? max(($sickLimit ?? 0) - ($sickUsed ?? 0), 0));
        $sickLimitValue = max((float) ($sickLimit ?? 0), 0.0);
        $sickUsedValue = (float) ($sickUsed ?? 0);
        $sickPercentUsed = $sickLimitValue > 0 ? min(($sickUsedValue / $sickLimitValue) * 100, 100) : 0;
        $otherAvailable = (float) max(($personalLimit ?? 0) - ($personalUsed ?? 0), 0);
        $otherLimit = max((float) ($personalLimit ?? 0), 0.0);
        $otherUsed = (float) ($personalUsed ?? 0);
        $otherPercentUsed = $otherLimit > 0 ? min(($otherUsed / $otherLimit) * 100, 100) : 0;
        $monthUsedDays = (float) ($totalDaysUsedCard ?? $totalDaysUsed ?? 0);
        $selectedMonthValue = $selectedMonth ?? now()->format('Y-m');
        $selectedMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonthValue)->format('F Y');
    @endphp
    <section class="employee-leave-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/40 bg-gradient-to-br from-slate-950 via-emerald-950 to-emerald-800 p-6 text-white shadow-xl md:p-8" style="--employee-leave-delay: 0ms;">
        <div class="absolute -right-12 -top-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-28 w-28 rounded-full bg-emerald-300/10 blur-3xl"></div>
        <div class="relative grid gap-6 xl:grid-cols-[1.5fr_0.9fr] xl:items-end">
            <div class="space-y-5">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">
                    Leave Desk
                </div>
                <div>
                    <h3 class="text-3xl font-black tracking-tight md:text-4xl">Manage balances, track requests, and file the next form faster.</h3>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-50 md:text-base">
                        Review your available credits for {{ $selectedMonthLabel }}, monitor request statuses, and switch between leave and official business forms in one place.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="employee-leave-card-motion employee-leave-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-leave-delay: 80ms;">
                        <p class="text-xs uppercase tracking-wide text-emerald-100">Total Requests</p>
                        <p class="mt-2 text-2xl font-black">{{ $totalRequestCount }}</p>
                    </div>
                    <div class="employee-leave-card-motion employee-leave-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-leave-delay: 120ms;">
                        <p class="text-xs uppercase tracking-wide text-lime-100">Approved</p>
                        <p class="mt-2 text-2xl font-black">{{ $approvedCount }}</p>
                    </div>
                    <div class="employee-leave-card-motion employee-leave-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-leave-delay: 160ms;">
                        <p class="text-xs uppercase tracking-wide text-emerald-100">Pending</p>
                        <p class="mt-2 text-2xl font-black">{{ $pendingCount }}</p>
                    </div>
                    <div class="employee-leave-card-motion employee-leave-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-leave-delay: 200ms;">
                        <p class="text-xs uppercase tracking-wide text-emerald-100">Rejected</p>
                        <p class="mt-2 text-2xl font-black">{{ $rejectedCount }}</p>
                    </div>
                </div>
            </div>

            <div class="employee-leave-card-motion employee-leave-reveal rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur-sm" style="--employee-leave-delay: 120ms;">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Month Filter</p>
                        <h4 class="mt-2 text-xl font-bold text-white">{{ $selectedMonthLabel }}</h4>
                        <p class="mt-1 text-sm text-emerald-50">Refresh leave balances and request records for a different month.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="relative group">
                            <button class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20">
                                <i class="fa fa-user"></i>
                            </button>

                            <div class="absolute right-0 z-50 mt-3 invisible w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100">
                                <a href="{{ route('employee.employeeProfile', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fa fa-user"></i>
                                    My Profile
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    @if (request()->filled('tab_session'))
                                        <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
                                    @endif
                                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50">
                                        <i class="fa fa-sign-out"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="employee-leave-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-emerald-100" style="--employee-leave-delay: 220ms;">
                            <i class="fa fa-calendar fa-lg"></i>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('employee.employeeLeave') }}" class="mt-5 space-y-3">
                    <label class="block text-sm font-medium text-emerald-50">Selected month</label>
                    <input
                        type="month"
                        name="month"
                        value="{{ $selectedMonthValue }}"
                        class="w-full rounded-xl border border-white/15 bg-white/90 px-4 py-3 text-sm text-slate-900 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                    />
                    <input type="hidden" name="form" value="{{ $activeEmployeeForm }}">
                    <button type="submit" class="w-full rounded-xl bg-emerald-300 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-emerald-200">
                        Apply Month Filter
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
        <article class="employee-leave-card-motion employee-leave-reveal rounded-[1.75rem] border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm" style="--employee-leave-delay: 120ms;">
            <div class="flex items-start justify-between gap-4">
                <div class="employee-leave-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-lg shadow-blue-500/20" style="--employee-leave-delay: 180ms;">
                    <i class="fa fa-calendar fa-2x"></i>
                </div>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">{{ rtrim(rtrim(number_format($vacationPercentUsed, 1, '.', ''), '0'), '.') }}% used</span>
            </div>
            <h3 class="mt-8 text-4xl font-black text-slate-900">{{ rtrim(rtrim(number_format($vacationAvailable, 1, '.', ''), '0'), '.') }}</h3>
            <p class="mt-1 text-sm font-medium text-slate-600">Vacation Leave</p>
            <p class="mt-4 text-xs leading-5 text-slate-500">Available out of {{ rtrim(rtrim(number_format($vacationLimit, 1, '.', ''), '0'), '.') }} days with {{ rtrim(rtrim(number_format($vacationUsed, 1, '.', ''), '0'), '.') }} day(s) already used.</p>
            <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-blue-100">
                <div class="employee-leave-progress-fill h-full rounded-full bg-blue-500" style="width: {{ $vacationPercentUsed }}%; --employee-leave-delay: 220ms;"></div>
            </div>
        </article>

        <article class="employee-leave-card-motion employee-leave-reveal rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm" style="--employee-leave-delay: 160ms;">
            <div class="flex items-start justify-between gap-4">
                <div class="employee-leave-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20" style="--employee-leave-delay: 220ms;">
                    <i class="fa fa-bed fa-2x"></i>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ rtrim(rtrim(number_format($sickPercentUsed, 1, '.', ''), '0'), '.') }}% used</span>
            </div>
            <h3 class="mt-8 text-4xl font-black text-slate-900">{{ rtrim(rtrim(number_format($sickAvailable, 1, '.', ''), '0'), '.') }}</h3>
            <p class="mt-1 text-sm font-medium text-slate-600">Sick Leave</p>
            <p class="mt-4 text-xs leading-5 text-slate-500">Available out of {{ rtrim(rtrim(number_format($sickLimitValue, 1, '.', ''), '0'), '.') }} days with {{ rtrim(rtrim(number_format($sickUsedValue, 1, '.', ''), '0'), '.') }} day(s) already used.</p>
            <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-emerald-100">
                <div class="employee-leave-progress-fill h-full rounded-full bg-emerald-500" style="width: {{ $sickPercentUsed }}%; --employee-leave-delay: 260ms;"></div>
            </div>
        </article>

        <article class="employee-leave-card-motion employee-leave-reveal rounded-[1.75rem] border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-6 shadow-sm" style="--employee-leave-delay: 200ms;">
            <div class="flex items-start justify-between gap-4">
                <div class="employee-leave-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg shadow-violet-500/20" style="--employee-leave-delay: 260ms;">
                    <i class="fa fa-calendar-o fa-2x"></i>
                </div>
                <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">{{ rtrim(rtrim(number_format($otherPercentUsed, 1, '.', ''), '0'), '.') }}% used</span>
            </div>
            <h3 class="mt-8 text-4xl font-black text-slate-900">{{ rtrim(rtrim(number_format($otherAvailable, 1, '.', ''), '0'), '.') }}</h3>
            <p class="mt-1 text-sm font-medium text-slate-600">Other Leave</p>
            <p class="mt-4 text-xs leading-5 text-slate-500">Available out of {{ rtrim(rtrim(number_format($otherLimit, 1, '.', ''), '0'), '.') }} days with {{ rtrim(rtrim(number_format($otherUsed, 1, '.', ''), '0'), '.') }} day(s) already used.</p>
            <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-violet-100">
                <div class="employee-leave-progress-fill h-full rounded-full bg-violet-500" style="width: {{ $otherPercentUsed }}%; --employee-leave-delay: 300ms;"></div>
            </div>
        </article>

        <article class="employee-leave-card-motion employee-leave-reveal rounded-[1.75rem] border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm" style="--employee-leave-delay: 240ms;">
            <div class="flex items-start justify-between gap-4">
                <div class="employee-leave-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20" style="--employee-leave-delay: 300ms;">
                    <i class="fa fa-hourglass-half fa-2x"></i>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ $selectedMonthLabel }}</span>
            </div>
            <h3 class="mt-8 text-4xl font-black text-slate-900">{{ rtrim(rtrim(number_format($monthUsedDays, 1, '.', ''), '0'), '.') }}</h3>
            <p class="mt-1 text-sm font-medium text-slate-600">Days Used</p>
            <p class="mt-4 text-xs leading-5 text-slate-500">Total leave days consumed in the selected month across all filed requests.</p>
            <div class="mt-5 rounded-2xl bg-amber-100/70 px-4 py-3 text-xs font-medium text-amber-800">
                Track this value monthly to spot heavy leave usage early.
            </div>
        </article>
    </section>

    <section id="leave-history-section" class="employee-leave-reveal rounded-[2rem] border border-slate-200 bg-white shadow-sm" style="--employee-leave-delay: 260ms;">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Request History</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">My Leave History</h3>
                <p class="mt-1 text-sm text-slate-500">Records for {{ $selectedMonthLabel }} as of {{ now()->format('M d, Y') }}.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ $totalRequestCount }}</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-emerald-600">Approved</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700">{{ $approvedCount }}</p>
                </div>
                <div class="rounded-2xl bg-amber-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-amber-600">Pending</p>
                    <p class="mt-1 text-xl font-bold text-amber-700">{{ $pendingCount }}</p>
                </div>
                <div class="rounded-2xl bg-rose-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-rose-600">Rejected</p>
                    <p class="mt-1 text-xl font-bold text-rose-700">{{ $rejectedCount }}</p>
                </div>
            </div>
        </div>
        <div class="max-h-96 overflow-y-auto px-6 py-4">
        @forelse ($monthRecords as $record)
            @php
                $startDate = $record['start_date_carbon'] ?? null;
                $endDate = $record['end_date_carbon'] ?? null;
                $days = (float) ($record['days'] ?? 0);
                $daysLabel = rtrim(rtrim(number_format($days, 1, '.', ''), '0'), '.');
                $dateLabel = '-';
                $statusLabel = ucfirst(strtolower((string) ($record['status'] ?? 'Pending')));
                $statusClass = 'bg-amber-100 text-amber-700';
                if (strcasecmp($statusLabel, 'Approved') === 0) {
                    $statusClass = 'bg-green-100 text-green-700';
                } elseif (strcasecmp($statusLabel, 'Rejected') === 0) {
                    $statusClass = 'bg-rose-100 text-rose-700';
                }
                if ($startDate && $endDate) {
                    $dateLabel = $startDate->isSameDay($endDate)
                        ? $startDate->format('M d, Y')
                        : $startDate->format('M d, Y').' - '. $endDate->format('M d, Y');
                }
            @endphp
            <div class="employee-leave-card-motion mb-4 flex flex-col gap-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5 last:mb-0 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-lg font-bold text-slate-900">{{ $record['leave_type'] ?? 'Leave' }}</p>
                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">{{ $daysLabel }} day(s)</span>
                    </div>
                    <p class="mt-2 text-sm font-medium text-slate-700">{{ $employeeDisplayName ?? ($record['employee_name'] ?? '-') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $dateLabel }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-500">{{ $record['reason'] ?? '-' }}</p>
                </div>
                <span class="inline-flex h-fit rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-200 text-slate-500">
                    <i class="fa fa-folder-open fa-2x"></i>
                </div>
                <h4 class="mt-5 text-xl font-bold text-slate-900">No leave records for {{ $selectedMonthLabel }}</h4>
                <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">Once you submit a leave or official business request, it will appear here with its current approval status.</p>
                <a href="#employee-form-panel" class="mt-5 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Open Request Form
                </a>
            </div>
        @endforelse
        </div>
    </section>

    <section id="employee-form-panel" class="employee-leave-reveal rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6" style="--employee-leave-delay: 300ms;">
        <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Request Forms</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Create a new request</h3>
                <p class="mt-1 text-sm text-slate-500">Switch between leave application and official business forms without leaving the page.</p>
            </div>
            <p class="text-sm text-slate-500">Employee: {{ $employeeFormName !== '' ? $employeeFormName : 'Not available' }}{{ $employeeFormPosition !== '' ? ' • '.$employeeFormPosition : '' }}</p>
        </div>

        <div class="flex flex-col gap-6 xl:flex-row">
            <div class="w-full xl:w-[320px] xl:min-w-[320px]">
                <div class="employee-leave-card-motion rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Select Form</h4>
                    <div class="mt-4 space-y-3 text-sm">
                        <a
                            href="{{ route('employee.employeeLeave', array_merge($employeeFormQueryBase, ['form' => 'leave'])) }}#employee-form-panel"
                            class="block rounded-2xl border px-4 py-4 transition {{ $activeEmployeeForm === 'leave' ? 'border-blue-200 bg-blue-50 text-blue-700 shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-blue-50' }}">
                            <p class="font-semibold">Leave Application Form</p>
                            <p class="mt-1 text-xs leading-5 {{ $activeEmployeeForm === 'leave' ? 'text-blue-600' : 'text-slate-500' }}">File vacation, sick, or other leave requests.</p>
                        </a>
                        <a
                            href="{{ route('employee.employeeLeave', array_merge($employeeFormQueryBase, ['form' => 'official'])) }}#employee-form-panel"
                            class="block rounded-2xl border px-4 py-4 transition {{ $activeEmployeeForm === 'official' ? 'border-violet-200 bg-violet-50 text-violet-700 shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-violet-200 hover:bg-violet-50' }}">
                            <p class="font-semibold">Official Business / Time</p>
                            <p class="mt-1 text-xs leading-5 {{ $activeEmployeeForm === 'official' ? 'text-violet-600' : 'text-slate-500' }}">Use this for approved external tasks and official time requests.</p>
                        </a>
                    </div>
                </div>
            </div>

            <div class="employee-leave-card-motion min-w-0 flex-1 overflow-x-auto rounded-[1.5rem] border border-slate-200 bg-white p-6 text-base md:p-8">
                @if ($activeEmployeeForm === 'official')
                    <div class="mb-6 text-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="mx-auto mb-2 h-28 w-auto">
                        <h3 class="text-xl font-bold text-gray-900">OFFICE OF THE HUMAN RESOURCE</h3>
                        <h3 class="text-xl font-bold text-gray-900">APPLICATION FOR OFFICIAL BUSINESS / OFFICIAL TIME</h3>
                    </div>
                    @include('requestForm.applicationOBF')
                @else
                    <div class="mb-6 text-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="mx-auto mb-2 h-28 w-auto">
                        <h3 class="text-xl font-bold text-gray-900">OFFICE OF THE HUMAN RESOURCE</h3>
                        <h3 class="text-xl font-bold text-gray-900">LEAVE APPLICATION FORM</h3>
                    </div>
                    @include('requestForm.leaveApplicationForm')
                @endif
            </div>
        </div>
    </section>

    </div>
</div>

    </main>
</div>
<script>
    const initEmployeeLeaveAnimation = () => {
        const page = document.getElementById('employee-leave-page');
        if (!page) return;

        const animatedItems = page.querySelectorAll('.employee-leave-reveal, .employee-leave-progress-fill');

        if (!('IntersectionObserver' in window)) {
            animatedItems.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.14,
            rootMargin: '0px 0px -40px 0px',
        });

        animatedItems.forEach((item) => observer.observe(item));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeLeaveAnimation, { once: true });
    } else {
        initEmployeeLeaveAnimation();
    }

    // Sidebar responsive adjustment
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
</script>

</body>
</html>




