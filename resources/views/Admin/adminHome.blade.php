<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>PeopleHub - HR Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .admin-display {
      font-family: "Arial Black", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      letter-spacing: 0;
    }
    .admin-kicker {
      letter-spacing: 0.22em;
    }
    .dashboard-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .dashboard-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .dashboard-reveal.is-visible {
      animation: dashboard-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--dashboard-delay, 0ms);
    }
    .dashboard-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .dashboard-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .dashboard-icon-pop {
      animation: dashboard-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--dashboard-delay, 0ms);
    }
    .dashboard-focus-pulse {
      animation: dashboard-soft-pulse 2.4s ease-in-out infinite;
    }
    .dashboard-progress-fill {
      transform-origin: left center;
      transform: scaleX(0);
      transition: transform 0.28s ease;
      will-change: transform;
    }
    .dashboard-progress-fill.is-visible {
      animation: dashboard-progress-grow 0.5s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--dashboard-delay, 90ms);
    }
    .dashboard-table-row {
      transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .dashboard-table-row:hover {
      transform: translateX(4px);
      box-shadow: inset 3px 0 0 rgba(16, 185, 129, 0.55);
    }
    .home-leave-approve-out {
      animation: home-leave-approve-out 0.48s cubic-bezier(0.2, 0.9, 0.2, 1) forwards;
      pointer-events: none;
    }
    .home-leave-decline-out {
      animation: home-leave-decline-out 0.5s cubic-bezier(0.22, 0.8, 0.24, 1) forwards;
      pointer-events: none;
    }
    .home-leave-approve-out::after,
    .home-leave-decline-out::after {
      border-radius: inherit;
      content: "";
      inset: 0;
      pointer-events: none;
      position: absolute;
    }
    .home-leave-approve-out::after {
      animation: home-leave-approve-glow 0.48s ease-out forwards;
      box-shadow: inset 0 0 0 2px rgba(16, 185, 129, 0.45);
    }
    .home-leave-decline-out::after {
      animation: home-leave-decline-glow 0.5s ease-out forwards;
      box-shadow: inset 0 0 0 2px rgba(244, 63, 94, 0.34);
    }
    .home-leave-enter {
      animation: home-leave-enter-up 0.36s cubic-bezier(0.2, 0.85, 0.22, 1) both;
    }
    [data-recent-employees-card].is-loading {
      opacity: 0.62;
      transform: translateY(4px);
      pointer-events: none;
    }
    .recent-employees-swap-in {
      animation: recent-employees-swap-in 0.32s cubic-bezier(0.2, 0.85, 0.22, 1) both;
    }
    @keyframes dashboard-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes dashboard-pop-in {
      0% {
        opacity: 0;
        transform: scale(0.82) rotate(-4deg);
      }
      100% {
        opacity: 1;
        transform: scale(1) rotate(0);
      }
    }
    @keyframes dashboard-soft-pulse {
      0%, 100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.24);
      }
      50% {
        box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
      }
    }
    @keyframes dashboard-progress-grow {
      from { transform: scaleX(0); }
      to { transform: scaleX(1); }
    }
    @keyframes home-leave-approve-out {
      0% {
        opacity: 1;
        transform: translateY(0) scale(1);
        background-color: rgba(248, 250, 252, 0.7);
      }
      45% {
        opacity: 1;
        transform: translateY(-8px) scale(1.01);
        background-color: rgba(236, 253, 245, 0.96);
      }
      100% {
        opacity: 0;
        transform: translateY(-18px) scale(0.96);
        background-color: rgba(236, 253, 245, 0.7);
      }
    }
    @keyframes home-leave-decline-out {
      0% {
        opacity: 1;
        transform: translateX(0) scale(1);
        background-color: rgba(248, 250, 252, 0.7);
      }
      24% {
        transform: translateX(-7px) rotate(-0.45deg);
        background-color: rgba(255, 241, 242, 0.95);
      }
      48% {
        opacity: 1;
        transform: translateX(5px) rotate(0.35deg);
      }
      100% {
        opacity: 0;
        transform: translateX(24px) scale(0.96);
        background-color: rgba(255, 241, 242, 0.7);
      }
    }
    @keyframes home-leave-approve-glow {
      from { opacity: 0.95; }
      to { opacity: 0; }
    }
    @keyframes home-leave-decline-glow {
      from { opacity: 0.9; }
      to { opacity: 0; }
    }
    @keyframes home-leave-enter-up {
      0% {
        opacity: 0;
        transform: translateY(18px) scale(0.98);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    @keyframes recent-employees-swap-in {
      0% {
        opacity: 0;
        transform: translateY(12px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @media (prefers-reduced-motion: reduce) {
      .dashboard-reveal,
      .dashboard-icon-pop,
      .dashboard-focus-pulse,
      .dashboard-progress-fill,
      .home-leave-approve-out,
      .home-leave-decline-out,
      .home-leave-enter,
      .recent-employees-swap-in,
      .home-leave-approve-out::after,
      .home-leave-decline-out::after {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .dashboard-card-motion,
      .dashboard-table-row {
        transition: none;
      }
      .dashboard-card-motion:hover,
      .dashboard-table-row:hover {
        transform: none;
      }
    }
  </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#f8fafc,_#eef2ff_40%,_#f8fafc_100%)] text-slate-900">
@php
  $employeeChange = (float) ($monthlyEmployeePercentChange ?? 0);
  $employeeChangeDirection = $employeeChange > 0 ? 'up' : ($employeeChange < 0 ? 'down' : 'flat');
  $employeeChangeClass = $employeeChangeDirection === 'up'
    ? 'text-emerald-300'
    : ($employeeChangeDirection === 'down' ? 'text-rose-300' : 'text-slate-300');
  $employeeChangeArrow = $employeeChangeDirection === 'up'
    ? '↑'
    : ($employeeChangeDirection === 'down' ? '↓' : '→');
  $employeeChangeSign = $employeeChange > 0 ? '+' : '';
  $totalDepartmentEmployees = max((int) ($departments->sum('count') ?? 0), 1);
@endphp

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    @include('components.adminHeader.dashboardHeader')

    <div class="space-y-8 p-4 pt-20 md:p-8">
      <section class="dashboard-reveal relative overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 px-6 py-7 text-white shadow-[0_30px_80px_rgba(15,23,42,0.22)] md:px-8">
        <div class="absolute -left-10 top-4 h-28 w-28 rounded-full bg-emerald-400/15 blur-3xl"></div>
        <div class="absolute right-10 top-0 h-24 w-24 rounded-full bg-sky-300/15 blur-3xl"></div>

        <div class="relative grid gap-8 xl:grid-cols-[1.4fr_0.9fr] xl:items-end">
          <div class="space-y-5">
            <div class="admin-kicker inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold uppercase text-emerald-100">
              Admin Command Center
            </div>
            <div>
              <h1 class="admin-display max-w-3xl text-3xl leading-tight text-white md:text-5xl">Lead hiring, leave requests, and approvals from one clean dashboard.</h1>
              <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-200 md:text-base">
                Watch workforce changes, review urgent requests, and move quickly between the HR tasks that matter most today.
              </p>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
              <div class="dashboard-card-motion dashboard-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--dashboard-delay: 30ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">Employees</p>
                <p class="admin-display mt-2 text-3xl text-white">{{ number_format($totalEmployeeCount ?? 0) }}</p>
                <p class="mt-1 text-xs {{ $employeeChangeClass }}">{{ $employeeChangeArrow }} {{ $employeeChangeSign }}{{ number_format($employeeChange, 1) }}% this month</p>
              </div>
              <div class="dashboard-card-motion dashboard-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--dashboard-delay: 60ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">On Leave</p>
                <p class="admin-display mt-2 text-3xl text-white">{{ number_format($onLeaveTodayCount ?? 0) }}</p>
                <p class="mt-1 text-xs text-amber-200">{{ number_format($pendingLeaveRequestCount ?? 0) }} pending requests</p>
              </div>
              <div class="dashboard-card-motion dashboard-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--dashboard-delay: 90ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">Open Roles</p>
                <p class="admin-display mt-2 text-3xl text-white">{{ number_format($openPositionsCount ?? 0) }}</p>
                <p class="mt-1 text-xs text-sky-200">{{ number_format($openPositionApplicationsCount ?? 0) }} applications</p>
              </div>
              <div class="dashboard-card-motion dashboard-reveal rounded-[1.35rem] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm" style="--dashboard-delay: 120ms;">
                <p class="admin-kicker text-[11px] font-semibold uppercase text-slate-300">Departments</p>
                <p class="admin-display mt-2 text-3xl text-white">{{ number_format($departments->count() ?? 0) }}</p>
                <p class="mt-1 text-xs text-emerald-200">Active workforce groups</p>
              </div>
            </div>
          </div>

          <div class="dashboard-reveal rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur-sm" style="--dashboard-delay: 80ms;">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="admin-kicker text-xs font-semibold uppercase text-emerald-100">Today's Focus</p>
                <h2 class="admin-display mt-2 text-2xl text-white">Keep operations moving</h2>
              </div>
              <div class="dashboard-icon-pop dashboard-focus-pulse flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-emerald-100" style="--dashboard-delay: 110ms;">
                <i class="fa-solid fa-shield-heart text-2xl"></i>
              </div>
            </div>

            <div class="mt-5 space-y-3">
              <div class="dashboard-card-motion flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                <div>
                  <p class="text-sm font-semibold text-white">Pending leave approvals</p>
                  <p class="text-xs text-slate-300">Requests waiting for admin action</p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">{{ number_format($pendingLeaveRequestCount ?? 0) }}</span>
              </div>
              <div class="dashboard-card-motion flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                <div>
                  <p class="text-sm font-semibold text-white">Active hiring pipeline</p>
                  <p class="text-xs text-slate-300">Applicants attached to open roles</p>
                </div>
                <span class="rounded-full bg-sky-100 px-3 py-1 text-sm font-semibold text-sky-700">{{ number_format($openPositionApplicationsCount ?? 0) }}</span>
              </div>
              <div class="dashboard-card-motion flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                <div>
                  <p class="text-sm font-semibold text-white">Department coverage</p>
                  <p class="text-xs text-slate-300">Teams currently represented</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">{{ number_format($departments->count() ?? 0) }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="dashboard-reveal rounded-[2rem] border border-slate-200 bg-white/85 p-6 shadow-sm backdrop-blur-sm" style="--dashboard-delay: 120ms;">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div>
            <p class="admin-kicker text-xs font-semibold uppercase text-sky-700">Quick Actions</p>
            <h2 class="admin-display mt-2 text-2xl text-slate-900">Jump into the next admin task.</h2>
          </div>
          <p class="text-sm text-slate-500">Shortcuts for the tools you use most often.</p>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
          <a href="{{ route('admin.adminEmployee') }}" class="dashboard-card-motion dashboard-reveal group rounded-[1.5rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5" style="--dashboard-delay: 150ms;">
            <div class="dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/25" style="--dashboard-delay: 170ms;">
              <i class="fa-solid fa-user-plus text-2xl"></i>
            </div>
            <h3 class="admin-display mt-5 text-lg text-slate-900">Manage Employees</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Review employee records and keep profiles organized.</p>
          </a>

          <a href="{{ route('admin.adminApplicant') }}" class="dashboard-card-motion dashboard-reveal group rounded-[1.5rem] border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-5" style="--dashboard-delay: 190ms;">
            <div class="dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-500 text-white shadow-lg shadow-sky-500/25" style="--dashboard-delay: 210ms;">
              <i class="fa-solid fa-id-card-clip text-2xl"></i>
            </div>
            <h3 class="admin-display mt-5 text-lg text-slate-900">Review Applicants</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Track incoming applications and move qualified candidates through the pipeline.</p>
          </a>

          <a href="{{ route('admin.adminPosition') }}" class="dashboard-card-motion dashboard-reveal group rounded-[1.5rem] border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-5" style="--dashboard-delay: 270ms;">
            <div class="dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg shadow-violet-500/25" style="--dashboard-delay: 290ms;">
              <i class="fa-solid fa-briefcase text-2xl"></i>
            </div>
            <h3 class="admin-display mt-5 text-lg text-slate-900">Open Positions</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Maintain job openings and see how many applicants are attached to each role.</p>
          </a>

          <a href="{{ route('admin.adminPayslip') }}" class="dashboard-card-motion dashboard-reveal group rounded-[1.5rem] border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5" style="--dashboard-delay: 310ms;">
            <div class="dashboard-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/25" style="--dashboard-delay: 330ms;">
              <i class="fa-solid fa-money-check-dollar text-2xl"></i>
            </div>
            <h3 class="admin-display mt-5 text-lg text-slate-900">Manage Payslips</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Upload, process, and review employee payslip records.</p>
          </a>
        </div>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.5fr_0.85fr]">
        <div class="space-y-6">
          <div data-recent-employees-card class="dashboard-reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition duration-200" style="--dashboard-delay: 180ms;">
            <div class="mb-5">
              <div>
                <p class="admin-kicker text-xs font-semibold uppercase text-emerald-700">Workforce</p>
                <h3 class="admin-display mt-2 text-2xl text-slate-900">Recent Employees</h3>
              </div>
            </div>

            <div class="overflow-x-auto rounded-[1.5rem] border border-slate-200">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                  <tr>
                    <th class="px-5 py-4 text-left font-semibold">Employee</th>
                    <th class="px-5 py-4 text-left font-semibold">Department</th>
                    <th class="px-5 py-4 text-left font-semibold">Status</th>
                    <th class="px-5 py-4 text-left font-semibold">Join Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                  @forelse($accept as $acc)
                  @php
                    $profilePhotoDocument = optional($acc->applicant)->documents
                      ?->first(function ($doc) {
                        return strtoupper(trim((string) ($doc->type ?? ''))) === 'PROFILE_PHOTO' && !empty($doc->filepath);
                      });
                    if (!$profilePhotoDocument) {
                      $profilePhotoDocument = optional($acc->applicant)->documents
                        ?->first(function ($doc) {
                          $mime = strtolower(trim((string) ($doc->mime_type ?? '')));
                          $filename = strtolower(trim((string) ($doc->filename ?? '')));
                          return !empty($doc->filepath) && (str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|gif|webp)$/i', $filename));
                        });
                    }
                    $profilePhotoUrl = $profilePhotoDocument?->filepath ? asset('storage/'.$profilePhotoDocument->filepath) : null;
                  @endphp
                  <tr class="dashboard-table-row bg-white transition hover:bg-slate-50/80">
                    <td class="px-5 py-4">
                      <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 text-sm font-bold text-white">
                          @if($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="Employee Photo" class="zoomable-profile-photo h-full w-full cursor-zoom-in object-cover" />
                          @else
                            {{ $acc->initials }}
                          @endif
                        </div>
                        <div class="min-w-0">
                          <p class="font-semibold text-slate-900">{{ trim($acc->first_name.' '.$acc->middle_name.' '.$acc->last_name) }}</p>
                          <p class="truncate text-xs text-slate-500">{{ $acc->email }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $acc->department ?? data_get($acc, 'employee.department') ?? data_get($acc, 'applicant.position.department') ?? 'Unassigned' }}</td>
                    <td class="px-5 py-4"><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span></td>
                    <td class="px-5 py-4 text-slate-600">{{ $acc->created_at_formatted ?? '-' }}</td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="4" class="px-5 py-8 text-center text-slate-400">No recent employees found.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            @if ($accept instanceof \Illuminate\Pagination\AbstractPaginator)
              <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600">
                  Showing
                  <span class="font-bold text-slate-900">{{ $accept->firstItem() ?? 0 }}</span>
                  to
                  <span class="font-bold text-slate-900">{{ $accept->lastItem() ?? 0 }}</span>
                  of
                  <span class="font-bold text-slate-900">{{ $accept->total() }}</span>
                  recent employees
                </p>
                <div class="flex items-center gap-2">
                  @if ($accept->onFirstPage())
                    <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300">
                      <i class="fa-solid fa-chevron-left text-xs"></i>
                    </span>
                  @else
                    <a data-recent-employees-page href="{{ $accept->previousPageUrl() }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                      <i class="fa-solid fa-chevron-left text-xs"></i>
                    </a>
                  @endif

                  <span class="rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-700">
                    {{ $accept->currentPage() }} / {{ $accept->lastPage() }}
                  </span>

                  @if ($accept->hasMorePages())
                    <a data-recent-employees-page href="{{ $accept->nextPageUrl() }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                      <i class="fa-solid fa-chevron-right text-xs"></i>
                    </a>
                  @else
                    <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300">
                      <i class="fa-solid fa-chevron-right text-xs"></i>
                    </span>
                  @endif
                </div>
              </div>
            @endif
          </div>

        </div>

        <div class="space-y-6">
          <div class="dashboard-reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm" style="--dashboard-delay: 240ms;">
            <div class="mb-5 flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-700">Urgent Queue</p>
                <h3 class="mt-2 text-2xl font-black text-slate-900">Leave Requests</h3>
              </div>
              <span data-home-leave-count class="flex h-9 min-w-[2.25rem] items-center justify-center rounded-full bg-rose-500 px-2 text-sm font-bold text-white">{{ (int) ($pendingLeaveRequestCount ?? 0) }}</span>
            </div>

            <div data-home-leave-list class="space-y-4">
              @forelse (($pendingLeaveRequestsForHome ?? collect()) as $request)
                @php
                  $requestName = trim((string) ($request->employee_name ?? ''));
                  if ($requestName === '') {
                    $requestName = 'Unknown Employee';
                  }
                  $nameParts = preg_split('/\s+/', $requestName) ?: [];
                  $initials = '';
                  foreach (array_slice($nameParts, 0, 2) as $part) {
                    $initials .= strtoupper(substr($part, 0, 1));
                  }
                  $initials = $initials !== '' ? $initials : 'NA';
                  $leaveType = (string) ($request->leave_type ?: 'Leave Request');
                  $startDate = $request->filing_date
                    ? \Carbon\Carbon::parse($request->filing_date)->startOfDay()
                    : \Carbon\Carbon::parse($request->created_at)->startOfDay();
                  $days = (float) ($request->number_of_working_days ?? 0);
                  if ($days <= 0) {
                    $days = max(
                      (float) ($request->days_with_pay ?? 0),
                      (float) ($request->applied_total ?? 0)
                    );
                  }
                  $rangeDays = max((int) ceil($days), 1);
                  $endDate = $startDate->copy()->addDays($rangeDays - 1);
                  $dateLabel = $startDate->isSameDay($endDate)
                    ? $startDate->format('M d, Y')
                    : $startDate->format('M d').' - '.$endDate->format('M d, Y');
                @endphp
                <div data-home-leave-card data-leave-request-id="{{ $request->id }}" class="dashboard-card-motion relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4 transition duration-200">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-sm font-bold text-white">{{ $initials }}</div>
                      <div>
                        <p class="font-semibold text-slate-900">{{ $requestName }}</p>
                        <p class="text-xs text-slate-500">{{ $leaveType }} - {{ $dateLabel }}</p>
                      </div>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending</span>
                  </div>
                  <div class="mt-4 flex gap-2">
                    <form data-home-leave-status-form class="flex-1" method="POST" action="{{ route('admin.updateLeaveRequestStatus', $request->id) }}">
                      @csrf
                      <input type="hidden" name="status" value="Approved">
                      <input type="hidden" name="redirect_back" value="1">
                      <button type="submit" class="w-full rounded-xl bg-emerald-500 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600">Approve</button>
                    </form>
                    <form data-home-leave-status-form class="flex-1" method="POST" action="{{ route('admin.updateLeaveRequestStatus', $request->id) }}">
                      @csrf
                      <input type="hidden" name="status" value="Rejected">
                      <input type="hidden" name="redirect_back" value="1">
                      <button type="submit" class="w-full rounded-xl bg-slate-200 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Decline</button>
                    </form>
                  </div>
                </div>
              @empty
                <div data-home-leave-empty class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
                  No pending leave requests.
                </div>
              @endforelse
            </div>
          </div>

          <div class="dashboard-reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm" style="--dashboard-delay: 260ms;">
            <div class="mb-5">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700">Distribution</p>
              <h3 class="mt-2 text-2xl font-black text-slate-900">Department Overview</h3>
            </div>

            <div class="space-y-4 text-sm">
              @php
                $colors = ['#10b981', '#3b82f6', '#f97316', '#8b5cf6', '#ec4899', '#14b8a6'];
                $colorIndex = 0;
              @endphp

              @forelse($departments as $dept)
                @php
                  $percentage = ($dept['count'] / $totalDepartmentEmployees) * 100;
                  $color = $colors[$colorIndex % count($colors)];
                  $colorIndex++;
                @endphp
                <div class="dashboard-card-motion rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                  <div class="flex items-center justify-between gap-3">
                    <span class="font-semibold text-slate-800">{{ $dept['name'] }}</span>
                    <span class="text-slate-500">{{ $dept['count'] }} ({{ round($percentage) }}%)</span>
                  </div>
                  <div class="mt-3 h-2.5 rounded-full bg-white">
                    <div class="dashboard-progress-fill h-2.5 rounded-full" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                  </div>
                </div>
              @empty
                <p class="text-slate-400">No department data available.</p>
              @endforelse
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
</div>

<div id="photo-lightbox" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/80 p-6">
  <img id="photo-lightbox-img" src="" alt="Zoomed employee photo" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl" />
</div>

<script>
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

  const lightbox = document.getElementById('photo-lightbox');
  const lightboxImg = document.getElementById('photo-lightbox-img');

  const closeLightbox = () => {
    if (!lightbox || !lightboxImg) return;
    lightbox.classList.add('hidden');
    lightbox.classList.remove('flex');
    lightboxImg.src = '';
  };

  document.addEventListener('click', (event) => {
    const photo = event.target.closest('.zoomable-profile-photo');
    if (!photo || !lightbox || !lightboxImg) return;

    const src = photo.getAttribute('src') || '';
    if (!src) return;
    lightboxImg.src = src;
    lightbox.classList.remove('hidden');
    lightbox.classList.add('flex');
  });

  lightbox?.addEventListener('click', (event) => {
    if (event.target === lightbox) closeLightbox();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeLightbox();
  });

  const replaceRecentEmployeesCard = async (url, shouldPushState = true) => {
    const currentCard = document.querySelector('[data-recent-employees-card]');
    if (!currentCard) return;

    currentCard.classList.add('is-loading');

    try {
      const response = await fetch(url, {
        headers: {
          Accept: 'text/html',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error('Unable to load recent employees.');
      }

      const html = await response.text();
      const documentFragment = new DOMParser().parseFromString(html, 'text/html');
      const nextCard = documentFragment.querySelector('[data-recent-employees-card]');
      if (!nextCard) {
        throw new Error('Recent employees content was not found.');
      }

      nextCard.classList.add('is-visible', 'recent-employees-swap-in');
      currentCard.replaceWith(nextCard);

      window.setTimeout(() => {
        nextCard.classList.remove('recent-employees-swap-in');
      }, 420);

      if (shouldPushState) {
        window.history.pushState({ recentEmployeesUrl: url }, '', url);
      }
    } catch (error) {
      currentCard.classList.remove('is-loading');
      window.location.href = url;
    }
  };

  document.addEventListener('click', (event) => {
    const pageLink = event.target.closest('[data-recent-employees-page]');
    if (!pageLink) return;

    event.preventDefault();
    replaceRecentEmployeesCard(pageLink.href);
  });

  window.addEventListener('popstate', () => {
    replaceRecentEmployeesCard(window.location.href, false);
  });

  const leaveList = document.querySelector('[data-home-leave-list]');
  const leaveCountBadge = document.querySelector('[data-home-leave-count]');
  const leaveCsrfToken = leaveList?.querySelector('input[name="_token"]')?.value
    || document.querySelector('meta[name="csrf-token"]')?.content
    || '';

  const setLeaveCount = (count) => {
    const safeCount = Math.max(count, 0);
    if (leaveCountBadge) leaveCountBadge.textContent = String(safeCount);
    window.updateAdminPendingLeaveCount?.(safeCount);
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[character]));

  const renderLeaveEmptyState = (pendingCount) => {
    if (!leaveList) return;
    leaveList.innerHTML = `
      <div data-home-leave-empty class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
        ${pendingCount > 0 ? 'More pending leave requests are available in Leave Management.' : 'No pending leave requests.'}
      </div>
    `;
  };

  const renderLeaveCard = (request) => {
    const actionUrl = escapeHtml(request.action_url || '');
    return `
      <div data-home-leave-card data-leave-request-id="${escapeHtml(request.id)}" class="dashboard-card-motion relative overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4 transition duration-200">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-sm font-bold text-white">${escapeHtml(request.initials || 'NA')}</div>
            <div>
              <p class="font-semibold text-slate-900">${escapeHtml(request.employee_name || 'Unknown Employee')}</p>
              <p class="text-xs text-slate-500">${escapeHtml(request.leave_type || 'Leave Request')} - ${escapeHtml(request.date_label || '')}</p>
            </div>
          </div>
          <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending</span>
        </div>
        <div class="mt-4 flex gap-2">
          <form data-home-leave-status-form class="flex-1" method="POST" action="${actionUrl}">
            <input type="hidden" name="_token" value="${escapeHtml(leaveCsrfToken)}">
            <input type="hidden" name="status" value="Approved">
            <input type="hidden" name="redirect_back" value="1">
            <button type="submit" class="w-full rounded-xl bg-emerald-500 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600">Approve</button>
          </form>
          <form data-home-leave-status-form class="flex-1" method="POST" action="${actionUrl}">
            <input type="hidden" name="_token" value="${escapeHtml(leaveCsrfToken)}">
            <input type="hidden" name="status" value="Rejected">
            <input type="hidden" name="redirect_back" value="1">
            <button type="submit" class="w-full rounded-xl bg-slate-200 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Decline</button>
          </form>
        </div>
      </div>
    `;
  };

  const captureLeaveCardPositions = () => {
    if (!leaveList || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return new Map();
    }

    return new Map(Array.from(leaveList.querySelectorAll('[data-home-leave-card]')).map((card) => [
      card.dataset.leaveRequestId,
      card.getBoundingClientRect(),
    ]));
  };

  const animateLeaveQueueMoveUp = (previousPositions) => {
    if (!leaveList || !previousPositions.size || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }

    leaveList.querySelectorAll('[data-home-leave-card]').forEach((card, index) => {
      const previousPosition = previousPositions.get(card.dataset.leaveRequestId);
      const currentPosition = card.getBoundingClientRect();

      if (previousPosition) {
        const deltaY = previousPosition.top - currentPosition.top;
        if (Math.abs(deltaY) < 1) return;

        card.animate([
          { transform: `translateY(${deltaY}px)` },
          { transform: 'translateY(0)' },
        ], {
          duration: 360,
          easing: 'cubic-bezier(0.2, 0.85, 0.22, 1)',
        });
        return;
      }

      card.classList.add('home-leave-enter');
      card.style.animationDelay = `${Math.min(index * 45, 120)}ms`;
      window.setTimeout(() => {
        card.classList.remove('home-leave-enter');
        card.style.animationDelay = '';
      }, 520);
    });
  };

  const renderLeaveQueue = (requests, pendingCount, previousPositions = new Map()) => {
    if (!leaveList) return;
    setLeaveCount(pendingCount);
    if (!Array.isArray(requests) || requests.length === 0) {
      renderLeaveEmptyState(pendingCount);
      return;
    }
    leaveList.innerHTML = requests.map(renderLeaveCard).join('');
    requestAnimationFrame(() => animateLeaveQueueMoveUp(previousPositions));
  };

  const playLeaveDecisionAnimation = (card, decision) => new Promise((resolve) => {
    if (!card || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      resolve();
      return;
    }

    const animationClass = decision === 'approved'
      ? 'home-leave-approve-out'
      : 'home-leave-decline-out';

    card.classList.add(animationClass);
    window.setTimeout(resolve, decision === 'approved' ? 480 : 500);
  });

  leaveList?.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-home-leave-status-form]');
    if (!form) return;

    event.preventDefault();

    const card = form.closest('[data-home-leave-card]');
    const buttons = Array.from(card?.querySelectorAll('button[type="submit"]') || []);
    const submitButton = event.submitter || form.querySelector('button[type="submit"]');
    const originalLabel = submitButton?.textContent || '';
    const formData = new FormData(form);
    const decision = String(formData.get('status') || '').toLowerCase();

    buttons.forEach((button) => {
      button.disabled = true;
      button.classList.add('cursor-wait', 'opacity-70');
    });
    if (submitButton) {
      submitButton.textContent = decision === 'approved' ? 'Approving...' : 'Declining...';
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error('Unable to update leave request.');
      }

      const data = await response.json();
      if (submitButton) {
        submitButton.textContent = decision === 'approved' ? 'Approved' : 'Declined';
      }
      const previousPositions = captureLeaveCardPositions();
      await playLeaveDecisionAnimation(card, decision);
      renderLeaveQueue(data.pending_requests || [], Number.parseInt(data.pending_count ?? '0', 10) || 0, previousPositions);
    } catch (error) {
      buttons.forEach((button) => {
        button.disabled = false;
        button.classList.remove('cursor-wait', 'opacity-70');
      });
      if (submitButton) {
        submitButton.textContent = originalLabel;
      }
      alert(error.message || 'Unable to update leave request. Please try again.');
    }
  });

  const revealOnScroll = () => {
    const revealItems = Array.from(document.querySelectorAll('.dashboard-reveal, .dashboard-progress-fill'));
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
    document.addEventListener('DOMContentLoaded', revealOnScroll, { once: true });
  } else {
    revealOnScroll();
  }
</script>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
