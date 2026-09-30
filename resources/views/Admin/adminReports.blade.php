<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HRIS Reports</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .report-card { transition: transform 220ms ease, box-shadow 220ms ease; }
    .report-card:hover { transform: translateY(-3px); box-shadow: 0 22px 48px rgba(15, 23, 42, 0.10); }
    @keyframes report-icon-float {
      0%, 100% { translate: 0 0; }
      50% { translate: 0 -4px; }
    }
    @keyframes report-ambient-drift {
      0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.35; }
      50% { transform: translate(-25px, 18px) scale(1.15); opacity: 0.65; }
    }
    @keyframes report-highlight-flow {
      0%, 20% { transform: translateX(-110%); }
      80%, 100% { transform: translateX(110%); }
    }
    #admin-reports-page .report-card {
      position: relative;
      isolation: isolate;
      overflow: hidden;
    }
    #admin-reports-page .report-card::before {
      content: '';
      position: absolute;
      z-index: -1;
      pointer-events: none;
      top: -55px;
      right: -40px;
      width: 180px;
      height: 180px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(56, 189, 248, 0.2), transparent 70%);
      animation: report-ambient-drift 9s ease-in-out infinite;
    }
    #admin-reports-page .report-card:nth-child(2)::before {
      background: radial-gradient(circle, rgba(16, 185, 129, 0.2), transparent 70%);
      animation-delay: -2s;
    }
    #admin-reports-page .report-card:nth-child(3)::before {
      background: radial-gradient(circle, rgba(245, 158, 11, 0.2), transparent 70%);
      animation-delay: -4s;
    }
    #admin-reports-page .report-card:nth-child(4)::before {
      background: radial-gradient(circle, rgba(244, 63, 94, 0.2), transparent 70%);
      animation-delay: -6s;
    }
    #admin-reports-page .report-card > div:first-child > span:first-child {
      animation: report-icon-float 4s ease-in-out infinite;
    }
    #admin-reports-page .report-card:nth-child(even) > div:first-child > span:first-child {
      animation-delay: -2s;
    }
    #admin-reports-page [data-report-bar] {
      position: relative;
      overflow: hidden;
    }
    #admin-reports-page [data-report-bar]::after {
      content: '';
      position: absolute;
      pointer-events: none;
      inset: 0;
      background: linear-gradient(100deg, transparent 20%, rgba(255,255,255,0.28) 50%, transparent 80%);
      animation: report-highlight-flow 6s ease-in-out infinite;
    }
    #admin-reports-page.report-motion-paused *,
    #admin-reports-page.report-motion-paused *::before,
    #admin-reports-page.report-motion-paused *::after {
      animation-play-state: paused !important;
    }
    @media print {
      #admin-reports-page *, #admin-reports-page *::before, #admin-reports-page *::after {
        animation: none !important;
      }
      #admin-reports-page .report-card::before,
      #admin-reports-page [data-report-bar]::after { display: none; }
    }
    #admin-reports-page > section > .rounded-3xl {
      transition: box-shadow 240ms ease, border-color 240ms ease;
    }
    #admin-reports-page button {
      transition: transform 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
    }
    #admin-reports-page button:active { transform: scale(0.97); }
    #admin-reports-page input { transition: border-color 180ms ease, box-shadow 180ms ease; }
    #admin-reports-page .report-card i { transition: transform 240ms ease; }
    @media (hover: hover) and (pointer: fine) {
      #admin-reports-page > section > .rounded-3xl:hover {
        border-color: #a7f3d0;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
      }
      #admin-reports-page .report-card:hover i { transform: translateY(-2px) rotate(-6deg); }
      #admin-reports-page button:hover { transform: translateY(-2px); }
      #admin-reports-page button:active { transform: scale(0.97); }
    }
    @media (prefers-reduced-motion: reduce) {
      #admin-reports-page *, #admin-reports-page *::before, #admin-reports-page *::after {
        animation: none !important;
        transition: none !important;
      }
      #admin-reports-page .report-card:hover,
      #admin-reports-page .report-card:hover i,
      #admin-reports-page button { transform: none !important; }
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-sky-50"] {
      background: #14283a !important;
      color: #7dd3fc !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-emerald-50"] {
      background: #153129 !important;
      color: #6ee7b7 !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-amber-50"],
    html[data-theme="dark"] #admin-reports-page [class*="bg-yellow-50"],
    html[data-theme="dark"] #admin-reports-page [class*="bg-orange-50"] {
      background: #342a17 !important;
      color: #fcd34d !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-rose-50"],
    html[data-theme="dark"] #admin-reports-page [class*="bg-pink-50"] {
      background: #351d29 !important;
      color: #fda4af !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-violet-50"],
    html[data-theme="dark"] #admin-reports-page [class*="bg-indigo-50"] {
      background: #282342 !important;
      color: #c4b5fd !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-cyan-50"] {
      background: #143039 !important;
      color: #67e8f9 !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-blue-50"] {
      background: #172554 !important;
      color: #93c5fd !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-lime-50"] {
      background: #253314 !important;
      color: #bef264 !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-teal-50"] {
      background: #153532 !important;
      color: #5eead4 !important;
    }
    html[data-theme="dark"] #admin-reports-page [class*="bg-red-50"] {
      background: #3b1d24 !important;
      color: #fca5a5 !important;
    }
    html[data-theme="dark"] #admin-reports-page svg text {
      fill: #cbd5e1 !important;
    }
    html[data-theme="dark"] #admin-reports-page svg line {
      stroke: #334155 !important;
    }
    html[data-theme="dark"] #admin-reports-page .report-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 24px rgba(2, 6, 23, .28);
    }

    @media screen and (max-width: 767px) {
      #admin-reports-page {
        padding: 4.75rem 0.75rem 1rem !important;
      }
      #admin-reports-page > section + section {
        margin-top: 0.875rem !important;
      }
      #admin-reports-page > section {
        gap: 0.75rem !important;
      }
      #admin-reports-page h1 {
        margin-top: 0.25rem;
        font-size: 1.5rem;
        line-height: 1.25;
      }
      #admin-reports-page > section:first-child {
        gap: 0.75rem !important;
      }
      #admin-reports-page > section:first-child form {
        gap: 0.375rem;
        padding: 0.375rem;
        border-radius: 0.875rem;
      }
      #admin-reports-page > section:first-child form input {
        min-width: 0;
        flex: 1 1 9rem;
        padding: 0.5rem 0.625rem;
      }
      #admin-reports-page .report-card,
      #admin-reports-page > section:not(:first-child) > div {
        padding: 0.875rem !important;
        border-radius: 1rem !important;
      }
      #admin-reports-page .report-card p.mt-5 {
        margin-top: 0.625rem;
        font-size: 1.5rem;
      }
      #admin-reports-page .report-card {
        min-width: 0;
      }
      #admin-reports-page .report-card > div:first-child {
        gap: 0.375rem;
      }
      #admin-reports-page .report-card > div:first-child > span:first-child {
        width: 2.25rem;
        height: 2.25rem;
        flex: 0 0 auto;
        border-radius: 0.75rem;
      }
      #admin-reports-page .report-card > div:first-child > span:last-child {
        min-width: 0;
        padding: 0.25rem 0.45rem;
        font-size: 0.6rem;
        overflow-wrap: anywhere;
      }
      #admin-reports-page .report-card > p:last-child {
        font-size: 0.7rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
      }
      #admin-reports-page h2 {
        font-size: 1rem;
        line-height: 1.35;
      }
      #admin-reports-page [class~="mt-6"] {
        margin-top: 0.875rem;
      }
      #admin-reports-page svg.h-72,
      #admin-reports-page div.h-72 {
        height: 12rem;
      }
      #admin-reports-page .h-44.w-44 {
        width: 8.5rem;
        height: 8.5rem;
      }
      #admin-reports-page .h-44.w-44 > .h-28.w-28 {
        width: 5.5rem;
        height: 5.5rem;
      }
      #admin-reports-page .grid-cols-2 > div[class*="p-4"] {
        padding: 0.75rem;
      }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-800">
<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="ml-16 min-w-0 flex-1 transition-all duration-300">
    @include('components.adminHeader.reportsHeader')

    @php
      $maxDepartment = max((int) ($departmentCounts->max() ?? 0), 1);
      $maxVolume = max((int) ($recordVolume->max() ?? 0), 1);
      $maxLeaveDays = max((float) ($leaveTypeDays->max() ?? 0), 1);
      $totalLeaveStatuses = max((int) $leaveStatusCounts->sum(), 1);
      $totalResignationStatuses = max((int) $resignationStatusCounts->sum(), 1);
      $payrollProcessedPercent = $totalEmployees > 0 ? round(($employeesWithPayslips / $totalEmployees) * 100) : 0;
      $maxJoinYear = max((int) ($joinYearCounts->max() ?? 0), 1);
      $joinYearPoints = $joinYearCounts->values()->map(function ($count, $index) use ($maxJoinYear) {
        $x = 28 + ($index * 52);
        $y = 158 - (((float) $count / $maxJoinYear) * 118);
        return $x.','.$y;
      })->implode(' ');
    @endphp

    <div id="admin-reports-page" class="min-w-0 space-y-6 p-4 pt-20 md:p-8">
      <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <div class="flex flex-wrap items-center gap-3">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Records Report</p>
            <button id="report-motion-toggle" type="button" aria-pressed="false" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">Pause motion</button>
          </div>
          <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Whole HRIS data overview</h1>
          <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">A full admin snapshot of employees, attendance, leave, documents, payslips, resignations, hiring, and communication records.</p>
        </div>
        <form method="GET" action="{{ route('admin.adminReports') }}" class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
          <label for="month" class="px-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Month</label>
          <input id="month" name="month" type="month" value="{{ $selectedMonth }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold outline-none focus:border-emerald-500">
          <button class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-700">
            <i class="fa-solid fa-filter"></i>
            Apply
          </button>
        </form>
      </section>

      <section class="grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
        <div class="report-card rounded-2xl border border-white bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between"><span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-100 text-sky-700"><i class="fa-solid fa-users"></i></span><span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">Employees</span></div>
          <p class="mt-5 text-3xl font-black text-slate-950">{{ number_format($totalEmployees) }}</p>
          <p class="mt-1 text-sm font-semibold text-slate-600">All employee records</p>
        </div>
        <div class="report-card rounded-2xl border border-white bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between"><span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-100 text-emerald-700"><i class="fa-solid fa-circle-check"></i></span><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Attendance</span></div>
          <p class="mt-5 text-3xl font-black text-slate-950">{{ number_format($attendanceRate, 1) }}%</p>
          <p class="mt-1 text-sm font-semibold text-slate-600">{{ number_format($attendancePresent) }} present of {{ number_format($attendanceTotal) }} rows</p>
        </div>
        <div class="report-card rounded-2xl border border-white bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between"><span class="grid h-11 w-11 place-items-center rounded-xl bg-amber-100 text-amber-700"><i class="fa-solid fa-calendar-minus"></i></span><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Leave</span></div>
          <p class="mt-5 text-3xl font-black text-slate-950">{{ number_format($leaveStatusCounts->sum()) }}</p>
          <p class="mt-1 text-sm font-semibold text-slate-600">Leave requests this month</p>
        </div>
        <div class="report-card rounded-2xl border border-white bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between"><span class="grid h-11 w-11 place-items-center rounded-xl bg-rose-100 text-rose-700"><i class="fa-solid fa-triangle-exclamation"></i></span><span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">Alerts</span></div>
          <p class="mt-5 text-3xl font-black text-slate-950">{{ number_format($attendanceAbsent + $attendanceTardy) }}</p>
          <p class="mt-1 text-sm font-semibold text-slate-600">Absence and tardiness records</p>
        </div>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Join Years</p>
              <h2 class="mt-1 text-xl font-black text-slate-950">Employees by year joined</h2>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ number_format($joinYearCounts->sum()) }} records</span>
          </div>
          <div class="mt-6 overflow-x-auto">
            @if ($joinYearCounts->isNotEmpty())
              <svg viewBox="0 0 640 190" class="h-72 min-w-[720px] w-full">
                <line x1="28" y1="158" x2="600" y2="158" stroke="#e2e8f0" stroke-width="2" />
                <line x1="28" y1="99" x2="600" y2="99" stroke="#e2e8f0" />
                <line x1="28" y1="40" x2="600" y2="40" stroke="#e2e8f0" />
                <polyline points="{{ $joinYearPoints }}" fill="none" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                @foreach ($joinYearCounts as $year => $count)
                  @php
                    $index = $loop->index;
                    $x = 28 + ($index * 52);
                    $y = 158 - (((float) $count / $maxJoinYear) * 118);
                  @endphp
                  <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#10b981" />
                  <text x="{{ $x }}" y="{{ max($y - 12, 14) }}" text-anchor="middle" font-size="11" font-weight="800" fill="#0f172a">{{ number_format($count) }}</text>
                  <text x="{{ $x }}" y="181" text-anchor="middle" font-size="11" font-weight="600" fill="#64748b">{{ $year }}</text>
                @endforeach
              </svg>
            @else
              <div class="flex h-full w-full items-center justify-center rounded-2xl bg-slate-50 p-6 text-sm font-semibold text-slate-500">
                No join year records yet.
              </div>
            @endif
          </div>
        </div>

        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-700">Donut Chart</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Employee payslip coverage</h2>
          <p class="mt-2 text-sm text-slate-500">{{ number_format($employeesWithPayslips) }} of {{ number_format($totalEmployees) }} employees have at least one payslip. All time; each employee counts once.</p>
          <div class="mt-6 flex justify-center">
            <div class="grid h-44 w-44 place-items-center rounded-full" style="background: conic-gradient(#10b981 0 {{ $payrollProcessedPercent }}%, #e2e8f0 {{ $payrollProcessedPercent }}% 100%);">
              <div class="grid h-28 w-28 place-items-center rounded-full bg-white text-center">
                <div><p class="text-3xl font-black text-slate-950">{{ $payrollProcessedPercent }}%</p><p class="text-xs font-bold text-slate-500">With payslips</p></div>
              </div>
            </div>
          </div>
          <div class="mt-6 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xl font-black">{{ number_format($employeesWithPayslips) }}</p><p class="text-xs font-semibold text-slate-500">Employees with payslips</p></div>
            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xl font-black">{{ number_format(max($totalEmployees - $employeesWithPayslips, 0)) }}</p><p class="text-xs font-semibold text-slate-500">Employees without payslips</p></div>
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-700">Horizontal Bars</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Employees by department</h2>
          <div class="mt-6 space-y-4">
            @php
              $departmentBarColors = ['#6366f1', '#10b981', '#f97316', '#0ea5e9', '#ec4899', '#8b5cf6', '#14b8a6', '#eab308'];
            @endphp
            @forelse ($departmentCounts as $department => $count)
              @php
                $departmentBarColor = $departmentBarColors[$loop->index % count($departmentBarColors)];
              @endphp
              <div>
                <div class="flex justify-between gap-4 text-sm"><span class="font-bold text-slate-700">{{ $department }}</span><span class="font-black">{{ number_format($count) }}</span></div>
                <div class="mt-2 h-3 rounded-full bg-slate-100"><div data-report-bar="horizontal" class="h-3 rounded-full" style="width: {{ max(5, round(($count / $maxDepartment) * 100)) }}%; background-color: {{ $departmentBarColor }};"></div></div>
              </div>
            @empty
              <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No department records yet.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Column Chart</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Whole records volume</h2>
          <div class="mt-6 flex h-72 items-end gap-4 overflow-x-auto border-b border-slate-200 pb-4">
            @foreach ($recordVolume as $label => $count)
              <div class="flex min-w-[5.5rem] flex-1 flex-col items-center justify-end gap-2">
                <span class="text-xs font-black text-slate-900">{{ number_format($count) }}</span>
                <div data-report-bar="vertical" class="w-full rounded-t-2xl bg-orange-500" style="height: {{ max(14, round(($count / $maxVolume) * 210)) }}px;"></div>
                <span class="text-center text-[11px] font-semibold leading-4 text-slate-500">{{ $label }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Status Bars</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Leave status</h2>
          <div class="mt-6 space-y-4">
            @forelse ($leaveStatusCounts as $status => $count)
              <div>
                <div class="flex justify-between text-sm"><span class="font-bold text-slate-700">{{ $status }}</span><span class="font-black">{{ $count }}</span></div>
                <div class="mt-2 h-2.5 rounded-full bg-slate-100"><div data-report-bar="horizontal" class="h-2.5 rounded-full bg-amber-500" style="width: {{ max(6, round(($count / $totalLeaveStatuses) * 100)) }}%;"></div></div>
              </div>
            @empty
              <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No leave requests this month.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Leave Days</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Leave by type</h2>
          <div class="mt-6 space-y-4">
            @forelse ($leaveTypeDays as $type => $days)
              <div>
                <div class="flex justify-between text-sm"><span class="font-bold text-slate-700">{{ $type }}</span><span class="font-black">{{ number_format($days, 1) }}</span></div>
                <div class="mt-2 h-2.5 rounded-full bg-slate-100"><div data-report-bar="horizontal" class="h-2.5 rounded-full bg-emerald-500" style="width: {{ max(6, round(($days / $maxLeaveDays) * 100)) }}%;"></div></div>
              </div>
            @empty
              <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No leave day totals yet.</p>
            @endforelse
          </div>
        </div>

        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Resignation</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Status breakdown</h2>
          <div class="mt-6 space-y-4">
            @forelse ($resignationStatusCounts as $status => $count)
              <div class="flex items-center gap-3">
                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-rose-50 text-sm font-black text-rose-700">{{ $count }}</div>
                <div class="min-w-0 flex-1">
                  <div class="flex justify-between text-sm"><span class="font-bold text-slate-700">{{ $status }}</span><span class="font-semibold text-slate-500">{{ round(($count / $totalResignationStatuses) * 100) }}%</span></div>
                  <div class="mt-2 h-2 rounded-full bg-slate-100"><div data-report-bar="horizontal" class="h-2 rounded-full bg-rose-500" style="width: {{ max(6, round(($count / $totalResignationStatuses) * 100)) }}%;"></div></div>
                </div>
              </div>
            @empty
              <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No resignation records yet.</p>
            @endforelse
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Record Inventory</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Other system totals</h2>
          <div class="mt-6 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-sky-50 p-4"><p class="text-2xl font-black">{{ number_format($genderCounts['male'] ?? 0) }}</p><p class="text-xs font-semibold text-slate-500">Male</p></div>
            <div class="rounded-2xl bg-rose-50 p-4"><p class="text-2xl font-black">{{ number_format($genderCounts['female'] ?? 0) }}</p><p class="text-xs font-semibold text-slate-500">Female</p></div>
            <div class="rounded-2xl bg-emerald-50 p-4"><p class="text-2xl font-black">{{ number_format($roleGroupCounts['heads'] ?? 0) }}</p><p class="text-xs font-semibold text-slate-500">Heads</p></div>
            <div class="rounded-2xl bg-cyan-50 p-4"><p class="text-2xl font-black">{{ number_format($roleGroupCounts['coordinators'] ?? 0) }}</p><p class="text-xs font-semibold text-slate-500">Coordinators</p></div>
            <div class="rounded-2xl bg-amber-50 p-4"><p class="text-2xl font-black">{{ number_format($roleGroupCounts['teaching'] ?? 0) }}</p><p class="text-xs font-semibold text-slate-500">Teaching</p></div>
            <div class="rounded-2xl bg-orange-50 p-4"><p class="text-2xl font-black">{{ number_format($roleGroupCounts['non_teaching'] ?? 0) }}</p><p class="text-xs font-semibold text-slate-500">Non-Teaching</p></div>
            <div class="rounded-2xl bg-blue-50 p-4 text-blue-700"><p class="text-2xl font-black">{{ number_format($roleGroupCounts['substitute'] ?? 0) }}</p><p class="text-xs font-semibold opacity-80">Substitute</p></div>
            <div class="rounded-2xl bg-lime-50 p-4 text-lime-700"><p class="text-2xl font-black">{{ number_format($openPositionCount) }}</p><p class="text-xs font-semibold opacity-80">Open Jobs</p></div>
            <div class="rounded-2xl bg-red-50 p-4 text-red-700"><p class="text-2xl font-black">{{ number_format($resignationCount) }}</p><p class="text-xs font-semibold opacity-80">Resignations</p></div>
          </div>
        </div>

        <div class="rounded-3xl border border-white bg-white p-6 shadow-sm">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Recent Activity</p>
          <h2 class="mt-1 text-xl font-black text-slate-950">Latest record changes</h2>
          <div class="mt-6 divide-y divide-slate-100">
            @forelse ($recentActivities as $activity)
              <div class="flex items-start gap-3 py-3">
                <span class="mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-red-100 text-red-600"><i class="fa-solid fa-clock-rotate-left"></i></span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-black text-slate-900">{{ $activity->action ?: 'System activity' }}</p>
                  <p class="mt-1 text-xs leading-5 text-slate-500">{{ $activity->description ?: 'No description recorded.' }}</p>
                  <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ $activity->user_name ?: 'System' }} | {{ optional($activity->created_at)->diffForHumans() }}</p>
                </div>
              </div>
            @empty
              <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-500">No activity logs yet.</p>
            @endforelse
          </div>
        </div>
      </section>
    </div>
  </main>
</div>

<script>
  (() => {
    const page = document.getElementById('admin-reports-page');
    const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
    const toggle = document.getElementById('report-motion-toggle');
    let motionPaused = false;
    const syncMotion = () => {
      page?.classList.toggle('report-motion-paused', motionPaused || document.hidden || motionPreference.matches);
      if (toggle) {
        toggle.hidden = motionPreference.matches;
        toggle.textContent = motionPaused ? 'Resume motion' : 'Pause motion';
        toggle.setAttribute('aria-pressed', String(motionPaused));
      }
    };
    toggle?.addEventListener('click', () => { motionPaused = !motionPaused; syncMotion(); });
    document.addEventListener('visibilitychange', syncMotion);
    motionPreference.addEventListener('change', syncMotion);
    syncMotion();
    if (!page || motionPreference.matches || !('IntersectionObserver' in window)
        || typeof Element.prototype.animate !== 'function') return;

    const activeAnimations = new Set();
    const play = (element, frames, options) => {
      const animation = element.animate(frames, options);
      activeAnimations.add(animation);
      animation.finished.catch(() => {}).finally(() => activeAnimations.delete(animation));
    };
    const observer = new IntersectionObserver((entries) => {
      let visibleIndex = 0;
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
        if (motionPreference.matches) return;
        const panel = entry.target;
        const delay = Math.min(visibleIndex++ * 65, 260);
        play(panel, [
          { opacity: 0, translate: '0 18px' },
          { opacity: 1, translate: '0 0' },
        ], { duration: 520, delay, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'backwards' });

        panel.querySelectorAll('[data-report-bar]').forEach((bar, index) => {
          const vertical = bar.dataset.reportBar === 'vertical';
          play(bar, [
            { transform: vertical ? 'scaleY(0)' : 'scaleX(0)', transformOrigin: vertical ? 'bottom' : 'left' },
            { transform: 'scale(1)', transformOrigin: vertical ? 'bottom' : 'left' },
          ], { duration: 750, delay: delay + Math.min(index * 45, 270), easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'backwards' });
        });
        panel.querySelectorAll('polyline').forEach((line) => {
          const length = line.getTotalLength();
          if (!length) return;
          play(line, [
            { strokeDasharray: `${length} ${length}`, strokeDashoffset: length },
            { strokeDasharray: `${length} ${length}`, strokeDashoffset: 0 },
          ], { duration: 1100, delay, easing: 'ease-out' });
        });
      });
    }, { threshold: 0.08 });

    page.querySelectorAll(':scope > section:first-child, :scope > section:not(:first-child) > div')
      .forEach((panel) => observer.observe(panel));
    const stopMotion = () => {
      observer.disconnect();
      activeAnimations.forEach((animation) => animation.cancel());
      activeAnimations.clear();
    };
    motionPreference.addEventListener('change', (event) => { if (event.matches) stopMotion(); });
    window.addEventListener('beforeprint', stopMotion);
    window.addEventListener('pagehide', stopMotion, { once: true });
  })();

  const sidebar = document.querySelector('aside');
  const main = document.querySelector('main');
  if (sidebar && main) {
    sidebar.addEventListener('mouseenter', function() {
      main.classList.remove('ml-16');
      main.classList.add('ml-64');
    });
    sidebar.addEventListener('mouseleave', function() {
      main.classList.remove('ml-64');
      main.classList.add('ml-16');
    });
  }
</script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
