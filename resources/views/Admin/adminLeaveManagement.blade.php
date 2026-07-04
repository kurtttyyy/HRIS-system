<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Leave Management</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .leave-management-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .leave-management-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .leave-management-reveal.is-visible {
      animation: leave-management-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--leave-management-delay, 0ms);
    }
    .leave-management-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .leave-management-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .leave-management-icon-pop {
      animation: leave-management-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--leave-management-delay, 0ms);
    }
    .leave-management-row-motion {
      transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .leave-management-row-motion:hover {
      transform: translateX(4px);
      box-shadow: inset 3px 0 0 rgba(16, 185, 129, 0.55), 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    #leave-summary-types-modal:not(.hidden) .leave-summary-panel {
      animation: leave-summary-panel-in 0.34s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .leave-summary-orb {
      animation: leave-summary-orb-float 6s ease-in-out infinite;
    }
    .leave-summary-progress {
      transform-origin: left;
      animation: leave-summary-progress-grow 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    @keyframes leave-management-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes leave-management-pop-in {
      0% {
        opacity: 0;
        transform: scale(0.82) rotate(-4deg);
      }
      100% {
        opacity: 1;
        transform: scale(1) rotate(0);
      }
    }
    @keyframes leave-summary-panel-in {
      from {
        opacity: 0;
        transform: translateY(22px) scale(0.96);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    @keyframes leave-summary-orb-float {
      0%, 100% { transform: translate3d(0, 0, 0); }
      50% { transform: translate3d(-10px, 8px, 0); }
    }
    @keyframes leave-summary-progress-grow {
      from { transform: scaleX(0); }
      to { transform: scaleX(1); }
    }
    @media (prefers-reduced-motion: reduce) {
      .leave-management-reveal,
      .leave-management-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .leave-management-card-motion,
      .leave-management-row-motion {
        transition: none;
      }
      .leave-management-card-motion:hover,
      .leave-management-row-motion:hover {
        transform: none;
      }
    }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#f7fafc_45%,#eefbf6_100%)] text-slate-800">
<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    @include('components.adminHeader.leaveHeader')

    <div id="leave-management-page" class="p-4 md:p-8 pt-20 space-y-6">
      @php
        $selectedMonthValue = $selectedMonth ?? now()->format('Y-m');
        $selectedMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonthValue)->format('F Y');
        $pendingRequestCount = (int) ($pendingRequestCount ?? ($pendingLeaveRequests ?? collect())->count());
        $visiblePendingRequestCount = ($pendingLeaveRequests ?? collect())->count();
        $approvedRequestCount = ($monthRecords ?? collect())->count();
        $rejectedRequestCount = (int) ($rejectedRequestCount ?? 0);
        $visibleApprovedRequestCount = ($recentMonthRecords ?? $monthRecords ?? collect())->count();
        $pendingLeaveDaysLabel = rtrim(rtrim(number_format((float) ($pendingLeaveDays ?? 0), 1, '.', ''), '0'), '.');
      @endphp

      @if (session('success'))
        <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
          {{ session('success') }}
        </div>
      @endif

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <button type="button" data-leave-summary-open data-summary-title="Leave Used This Month" data-summary-subtitle="Approved leave types and total days used." data-summary-details="{{ json_encode($leaveSummaryBreakdowns['leave_used'] ?? []) }}" class="leave-management-card-motion leave-management-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur focus:outline-none focus:ring-4 focus:ring-sky-200" style="--leave-management-delay: 30ms;">
          <span class="leave-management-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600" style="--leave-management-delay: 70ms;">
            <i class="fa-regular fa-calendar-check text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Leave Used This Month</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-slate-900">{{ number_format((int) ($totalLeaveUsedDays ?? 0)) }}</p>
          <p class="mt-1 text-sm text-slate-500">Total approved leave days</p>
        </button>

        <button type="button" data-leave-summary-open data-summary-title="Sick Leave Used" data-summary-subtitle="Approved sick leave request types and days used." data-summary-details="{{ json_encode($leaveSummaryBreakdowns['sick_used'] ?? []) }}" class="leave-management-card-motion leave-management-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur focus:outline-none focus:ring-4 focus:ring-blue-200" style="--leave-management-delay: 60ms;">
          <span class="leave-management-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600" style="--leave-management-delay: 100ms;">
            <i class="fa-solid fa-notes-medical text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Sick Leave Used</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-blue-700">{{ number_format((int) ($sickLeaveUsedDays ?? 0)) }}</p>
          <p class="mt-1 text-sm text-slate-500">Approved sick leave days</p>
        </button>

        <button type="button" data-leave-summary-open data-summary-title="Approved Requests" data-summary-subtitle="Approved requests grouped by leave type." data-summary-details="{{ json_encode($leaveSummaryBreakdowns['approved'] ?? []) }}" class="leave-management-card-motion leave-management-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur focus:outline-none focus:ring-4 focus:ring-emerald-200" style="--leave-management-delay: 90ms;">
          <span class="leave-management-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600" style="--leave-management-delay: 130ms;">
            <i class="fa-solid fa-circle-check text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Approved Requests</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-emerald-700">{{ number_format($approvedRequestCount) }}</p>
          <p class="mt-1 text-sm text-slate-500">Approved leave records in month</p>
        </button>

        <button type="button" data-leave-summary-open data-summary-title="Rejected Requests" data-summary-subtitle="Rejected requests grouped by leave type." data-summary-details="{{ json_encode($leaveSummaryBreakdowns['rejected'] ?? []) }}" class="leave-management-card-motion leave-management-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur focus:outline-none focus:ring-4 focus:ring-rose-200" style="--leave-management-delay: 120ms;">
          <span class="leave-management-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600" style="--leave-management-delay: 160ms;">
            <i class="fa-solid fa-circle-xmark text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Rejected Requests</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-rose-700">{{ number_format($rejectedRequestCount) }}</p>
          <p class="mt-1 text-sm text-slate-500">Rejected leave records in month</p>
        </button>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,1.15fr)]">
        <section class="leave-management-reveal overflow-hidden rounded-[1.75rem] border border-amber-100/80 bg-white/92 shadow-[0_22px_50px_rgba(15,23,42,0.07)] backdrop-blur" style="--leave-management-delay: 160ms;">
          <div class="border-b border-amber-100 bg-[linear-gradient(180deg,rgba(254,243,199,0.45),rgba(255,255,255,0.85))] px-5 py-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-white/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700">
                  Priority Queue
                </div>
                <h3 class="mt-3 text-xl font-black tracking-tight text-slate-900">Pending Leave Requests</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $selectedMonthLabel }} • {{ $pendingRequestCount }} request(s) • {{ $pendingLeaveDaysLabel }} day(s)</p>
                @if ($pendingRequestCount > $visiblePendingRequestCount)
                  <p class="mt-1 text-xs font-medium text-amber-700">Showing the {{ $visiblePendingRequestCount }} newest requests.</p>
                @endif
              </div>
            </div>
          </div>

          <div class="p-4 space-y-4">
            @forelse (($pendingLeaveRequests ?? collect()) as $request)
              @php
                $requestFilingDate = $request->filing_date ? \Carbon\Carbon::parse($request->filing_date)->format('M d, Y') : optional($request->created_at)->format('M d, Y');
                $requestDays = rtrim(rtrim(number_format((float) ($request->number_of_working_days ?? 0), 1, '.', ''), '0'), '.');
                $requestLeaveType = $request->leave_type ?: 'Leave Request';
                $requestDates = $request->inclusive_dates ?: '-';
                $requestReason = str_contains(strtolower((string) $requestLeaveType), 'official business')
                  ? 'Business Trip'
                  : (str_contains(strtolower((string) $requestLeaveType), 'annual leave') ? 'Personal vacation' : (str_contains(strtolower((string) $requestLeaveType), 'sick leave') ? 'Not fit for work due to health reasons' : $requestDates));
                $employeeName = trim((string) ($request->employee_name ?? '-'));
                $nameParts = array_values(array_filter(explode(' ', $employeeName)));
                $initials = strtoupper(substr($nameParts[0] ?? 'L', 0, 1).substr($nameParts[count($nameParts) - 1] ?? 'R', 0, 1));
                $medicalCertificateUrl = !empty($request->medical_receipt_path) ? asset('storage/'.$request->medical_receipt_path) : null;
                $medicalCertificateExtension = strtolower(pathinfo((string) ($request->medical_receipt_name ?? $request->medical_receipt_path ?? ''), PATHINFO_EXTENSION));
                $medicalCertificateMime = strtolower((string) ($request->medical_receipt_mime ?? ''));
                $isMedicalCertificatePdf = $medicalCertificateExtension === 'pdf' || str_contains($medicalCertificateMime, 'pdf');
                $isMedicalCertificateImage = in_array($medicalCertificateExtension, ['jpg', 'jpeg', 'png', 'webp'], true)
                  || in_array($medicalCertificateMime, ['image/jpeg', 'image/png', 'image/webp'], true);
                $formatLeaveValue = static function ($value) {
                  return rtrim(rtrim(number_format((float) ($value ?? 0), 1, '.', ''), '0'), '.');
                };
              @endphp
              <div class="leave-management-row-motion rounded-[1.5rem] border border-slate-200 bg-[linear-gradient(180deg,#fffef7,#ffffff)] p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                  <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-sm font-bold text-amber-700">
                      {{ $initials !== '' ? $initials : 'LR' }}
                    </div>
                    <div>
                      <div class="flex flex-wrap items-center gap-2">
                        <p class="text-base font-semibold text-slate-900">{{ $requestLeaveType }}</p>
                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">Pending</span>
                      </div>
                      <p class="mt-1 text-sm font-semibold text-slate-800">{{ $employeeName }}</p>
                      <p class="mt-1 text-sm text-slate-500">Filed: {{ $requestFilingDate }} • {{ $requestDays }} day(s)</p>
                      <p class="mt-1 text-sm text-slate-400">{{ $requestReason }}</p>
                      @if($medicalCertificateUrl)
                        <span class="mt-3 inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700">
                          <i class="fa-solid fa-file-medical"></i>
                          Medical certificate attached
                        </span>
                      @endif
                    </div>
                  </div>

                  <div class="flex items-center gap-2 shrink-0">
                    <button
                      type="button"
                      data-leave-review-open="leave-review-modal-{{ $request->id }}"
                      class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-700"
                    >
                      <i class="fa-regular fa-eye"></i>
                      Review Request
                    </button>
                  </div>
                </div>
              </div>

              <div id="leave-review-modal-{{ $request->id }}" class="fixed inset-0 z-[100] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="leave-review-title-{{ $request->id }}">
                <button type="button" data-leave-review-close class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" aria-label="Close review"></button>
                <div class="relative z-10 flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-[1.75rem] bg-white shadow-2xl">
                  <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4 md:px-7">
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600">Review before deciding</p>
                      <h3 id="leave-review-title-{{ $request->id }}" class="mt-1 text-xl font-black text-slate-900">{{ $requestLeaveType }}</h3>
                      <p class="mt-1 text-sm text-slate-500">{{ $employeeName }} • Filed {{ $requestFilingDate }}</p>
                    </div>
                    <button type="button" data-leave-review-close class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900" aria-label="Close review">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </div>

                  <div class="overflow-y-auto px-5 py-5 md:px-7">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                      <section>
                        <h4 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-500">Submitted Leave Form</h4>
                        <dl class="mt-3 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                          <div><dt class="text-xs font-semibold text-slate-400">Employee</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $employeeName }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Employee ID</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->employee_id ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Office / Department</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->office_department ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Position</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->position ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Date of Filing</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestFilingDate }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Salary</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->salary ?: '-' }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Leave Type</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestLeaveType }}</dd></div>
                          <div><dt class="text-xs font-semibold text-slate-400">Working Days</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestDays }} day(s)</dd></div>
                          <div class="sm:col-span-2"><dt class="text-xs font-semibold text-slate-400">Inclusive Dates</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $requestDates }}</dd></div>
                          <div class="sm:col-span-2"><dt class="text-xs font-semibold text-slate-400">Commutation</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $request->commutation ?: '-' }}</dd></div>
                        </dl>

                        <h4 class="mt-5 text-sm font-bold uppercase tracking-[0.14em] text-slate-500">Leave Credits</h4>
                        <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200">
                          <table class="w-full min-w-[520px] text-left text-sm">
                            <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-500">
                              <tr><th class="px-4 py-3">Balance</th><th class="px-4 py-3">Vacation</th><th class="px-4 py-3">Sick</th><th class="px-4 py-3">Total</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                              <tr><th class="px-4 py-3 font-semibold">Beginning</th><td class="px-4 py-3">{{ $formatLeaveValue($request->beginning_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->beginning_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->beginning_total) }}</td></tr>
                              <tr><th class="px-4 py-3 font-semibold">Earned</th><td class="px-4 py-3">{{ $formatLeaveValue($request->earned_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->earned_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->earned_total) }}</td></tr>
                              <tr><th class="px-4 py-3 font-semibold">Applied</th><td class="px-4 py-3">{{ $formatLeaveValue($request->applied_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->applied_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->applied_total) }}</td></tr>
                              <tr><th class="px-4 py-3 font-semibold">Ending</th><td class="px-4 py-3">{{ $formatLeaveValue($request->ending_vacation) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->ending_sick) }}</td><td class="px-4 py-3">{{ $formatLeaveValue($request->ending_total) }}</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </section>

                      <section>
                        <div class="flex items-center gap-3">
                          <h4 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-500">Medical Certificate</h4>
                        </div>

                        @if($medicalCertificateUrl && $isMedicalCertificateImage)
                          <button
                            type="button"
                            data-medical-image-zoom
                            data-image-src="{{ $medicalCertificateUrl }}"
                            data-image-alt="Medical certificate for {{ $employeeName }}"
                            class="group relative mt-3 block w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 text-left focus:outline-none focus:ring-4 focus:ring-blue-300"
                            aria-label="Enlarge medical certificate"
                          >
                            <img src="{{ $medicalCertificateUrl }}" alt="Medical certificate for {{ $employeeName }}" class="max-h-[520px] w-full cursor-zoom-in object-contain transition group-hover:brightness-90">
                            <span class="absolute bottom-3 left-1/2 inline-flex -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full bg-slate-950/85 px-4 py-2 text-sm font-semibold text-white shadow-lg">
                              <i class="fa-solid fa-magnifying-glass-plus"></i>
                              Click to enlarge
                            </span>
                          </button>
                        @elseif($medicalCertificateUrl && $isMedicalCertificatePdf)
                          <iframe src="{{ $medicalCertificateUrl }}" title="Medical certificate for {{ $employeeName }}" class="mt-3 h-[520px] w-full rounded-2xl border border-slate-200 bg-white"></iframe>
                        @elseif($medicalCertificateUrl)
                          <div class="mt-3 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-8 text-center">
                            <i class="fa-solid fa-file-medical text-3xl text-blue-600"></i>
                            <p class="mt-3 text-sm font-semibold text-slate-800">{{ $request->medical_receipt_name ?: 'Medical certificate' }}</p>
                            <p class="mt-1 text-xs text-slate-500">This file format must be opened in its original viewer.</p>
                          </div>
                        @else
                          <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                            <i class="fa-regular fa-file-lines text-3xl text-slate-400"></i>
                            <p class="mt-3 text-sm font-semibold text-slate-700">No medical certificate attached.</p>
                            <p class="mt-1 text-xs text-slate-500">Certificates are required for newly submitted Sick Leave requests.</p>
                          </div>
                        @endif
                      </section>
                    </div>
                  </div>

                  <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end md:px-7">
                    <button type="button" data-leave-review-close class="rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Close</button>
                    <form method="POST" action="{{ route('admin.updateLeaveRequestStatus', $request->id) }}">
                      @csrf
                      <input type="hidden" name="status" value="Rejected">
                      <input type="hidden" name="month" value="{{ $selectedMonthValue }}">
                      <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                        <i class="fa-solid fa-xmark"></i>
                        Reject
                      </button>
                    </form>
                    <form method="POST" action="{{ route('admin.updateLeaveRequestStatus', $request->id) }}">
                      @csrf
                      <input type="hidden" name="status" value="Approved">
                      <input type="hidden" name="month" value="{{ $selectedMonthValue }}">
                      <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <i class="fa-solid fa-check"></i>
                        Approve
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            @empty
              <div class="rounded-[1.5rem] border border-dashed border-amber-200 bg-amber-50/60 px-6 py-10 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-amber-500 shadow-sm">
                  <i class="fa-regular fa-calendar-check text-xl"></i>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-700">No pending leave requests for this month.</p>
                <p class="mt-1 text-sm text-slate-500">Everything is up to date for {{ $selectedMonthLabel }}.</p>
              </div>
            @endforelse
          </div>
        </section>

        <section data-approved-history-section class="leave-management-reveal overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/92 shadow-[0_22px_50px_rgba(15,23,42,0.07)] backdrop-blur" style="--leave-management-delay: 200ms;">
          <div class="border-b border-slate-200 bg-[linear-gradient(180deg,rgba(239,246,255,0.7),rgba(255,255,255,0.92))] px-5 py-4">
            <div class="flex flex-col gap-4">
              <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white/85 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">
                  Approved Timeline
                </div>
                <h3 class="mt-3 text-xl font-black tracking-tight text-slate-900">Leave History</h3>
                <p class="mt-1 text-sm text-slate-500">Approved records for {{ $selectedMonthLabel }}</p>
              </div>
              <div class="grid grid-cols-2 gap-2 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                  From
                  <input data-approved-history-from type="date" class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-700 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-200">
                </label>
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                  To
                  <input data-approved-history-to type="date" class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-700 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-200">
                </label>
                <button type="button" data-approved-history-reset class="col-span-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 sm:col-span-1">
                  Reset
                </button>
              </div>
              <p data-approved-history-count class="text-xs font-medium text-sky-700">Showing {{ $approvedRequestCount }} approved request(s)</p>
            </div>
          </div>

          <div data-approved-history-list class="max-h-[34rem] space-y-4 overflow-y-auto p-4">
            @forelse (($recentMonthRecords ?? $monthRecords ?? collect()) as $record)
              @php
                $leaveType = (string) ($record['leave_type'] ?? 'Leave');
                $startDate = $record['start_date_carbon'] ?? null;
                $endDate = $record['end_date_carbon'] ?? null;
                $days = (int) ($record['days'] ?? 0);
                $daysLabel = $days === 1 ? '1 day' : ($days.' days');
                $dateLabel = '-';
                if ($startDate && $endDate) {
                  $dateLabel = $startDate->isSameDay($endDate)
                    ? $startDate->format('M d, Y')
                    : $startDate->format('M d, Y').' - '.$endDate->format('M d, Y');
                }

                $iconMap = [
                  'Annual Leave' => 'fa-solid fa-umbrella-beach',
                  'Sick Leave' => 'fa-solid fa-notes-medical',
                  'Personal Leave' => 'fa-solid fa-user-clock',
                  'Study Leave' => 'fa-solid fa-graduation-cap',
                  'Emergency Leave' => 'fa-solid fa-triangle-exclamation',
                  'Maternity Leave' => 'fa-solid fa-baby',
                  'Paternity Leave' => 'fa-solid fa-people-roof',
                  'Bereavement Leave' => 'fa-solid fa-ribbon',
                  'Service Incentive Leave' => 'fa-solid fa-star',
                ];
                $colorMap = [
                  'Annual Leave' => 'bg-emerald-100 text-emerald-700',
                  'Sick Leave' => 'bg-blue-100 text-blue-700',
                  'Personal Leave' => 'bg-amber-100 text-amber-700',
                  'Study Leave' => 'bg-violet-100 text-violet-700',
                  'Emergency Leave' => 'bg-rose-100 text-rose-700',
                  'Maternity Leave' => 'bg-pink-100 text-pink-700',
                  'Paternity Leave' => 'bg-cyan-100 text-cyan-700',
                  'Bereavement Leave' => 'bg-slate-200 text-slate-700',
                  'Service Incentive Leave' => 'bg-yellow-100 text-yellow-700',
                ];
                $iconClass = $iconMap[$leaveType] ?? 'fa-regular fa-file-lines';
                $iconToneClass = $colorMap[$leaveType] ?? 'bg-slate-100 text-slate-700';
                $reasonLabel = str_contains(strtolower($leaveType), 'official business')
                  ? 'Business Trip'
                  : (str_contains(strtolower($leaveType), 'annual leave') ? 'Personal vacation' : (str_contains(strtolower($leaveType), 'sick leave') ? 'Not fit for work due to health reasons' : ($record['reason'] ?? '-')));
              @endphp
              <div
                data-approved-history-record
                data-approved-start="{{ $startDate?->format('Y-m-d') }}"
                data-approved-end="{{ $endDate?->format('Y-m-d') }}"
                class="leave-management-row-motion rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                  <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $iconToneClass }}">
                      <i class="{{ $iconClass }} text-lg"></i>
                    </div>
                    <div>
                      <div class="flex flex-wrap items-center gap-2">
                        <p class="text-base font-semibold text-slate-900">{{ $leaveType }}</p>
                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700">Employee</span>
                      </div>
                      <p class="mt-1 text-sm font-semibold text-slate-800">{{ $record['employee_name'] ?? '-' }}</p>
                      <p class="mt-1 text-sm text-slate-500">{{ $dateLabel }} • {{ $daysLabel }}</p>
                      <p class="mt-1 text-sm text-slate-400">{{ $reasonLabel }}</p>
                    </div>
                  </div>
                  <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Approved</span>
                </div>
              </div>
            @empty
              <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50/70 px-6 py-10 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                  <i class="fa-regular fa-folder-open text-xl"></i>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-700">No approved leave records for this month.</p>
                <p class="mt-1 text-sm text-slate-500">Approved leave history will appear here once requests are processed.</p>
              </div>
            @endforelse
            <div data-approved-history-filter-empty class="hidden rounded-[1.5rem] border border-dashed border-sky-200 bg-sky-50/60 px-6 py-10 text-center">
              <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-sky-500 shadow-sm">
                <i class="fa-solid fa-filter-circle-xmark text-xl"></i>
              </div>
              <p class="mt-4 text-sm font-bold text-slate-800">No approved leave in this date range</p>
              <p class="mt-1 text-sm text-slate-500">Choose another range or reset the date filter.</p>
            </div>
          </div>
        </section>
      </div>

      <div id="leave-summary-types-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/75 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="leave-summary-types-title">
        <div class="leave-summary-panel max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[2.25rem] border border-white/20 bg-[#f8fafc] shadow-[0_40px_110px_rgba(2,6,23,0.48)]">
          <div class="relative overflow-hidden bg-[linear-gradient(135deg,#020617_0%,#0f172a_45%,#064e3b_100%)] px-6 pb-16 pt-7 text-white md:px-8">
            <div class="leave-summary-orb absolute -right-10 -top-16 h-56 w-56 rounded-full bg-emerald-300/15 blur-3xl"></div>
            <div class="absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-cyan-300/10 blur-3xl"></div>
            <div class="absolute right-24 top-6 h-20 w-20 rounded-full border border-white/10 bg-white/5"></div>

            <div class="relative flex items-start justify-between gap-5">
              <div class="flex min-w-0 items-start gap-4">
                <div class="hidden h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-xl text-emerald-300 shadow-lg backdrop-blur sm:flex">
                  <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="min-w-0">
                  <div class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                    Live Request Insights
                  </div>
                  <h3 id="leave-summary-types-title" data-leave-summary-title class="mt-3 truncate text-2xl font-black tracking-tight md:text-3xl">Leave Requests</h3>
                  <p data-leave-summary-subtitle class="mt-2 max-w-xl text-sm leading-6 text-slate-300">Requests grouped by leave type.</p>
                </div>
              </div>
              <button type="button" data-leave-summary-close class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition hover:rotate-90 hover:bg-white/20" aria-label="Close request type breakdown">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>

          <div class="relative -mt-9 px-5 md:px-8">
            <div class="grid grid-cols-3 gap-3">
              <div class="rounded-2xl border border-white bg-white p-4 shadow-[0_14px_30px_rgba(15,23,42,0.1)]">
                <div class="flex items-center gap-2 text-sky-600">
                  <i class="fa-solid fa-layer-group text-xs"></i>
                  <p class="text-[10px] font-bold uppercase tracking-wide">Types</p>
                </div>
                <p data-leave-summary-stat="types" class="mt-2 text-2xl font-black text-slate-900">0</p>
              </div>
              <div class="rounded-2xl border border-white bg-white p-4 shadow-[0_14px_30px_rgba(15,23,42,0.1)]">
                <div class="flex items-center gap-2 text-emerald-600">
                  <i class="fa-solid fa-file-circle-check text-xs"></i>
                  <p class="text-[10px] font-bold uppercase tracking-wide">Requests</p>
                </div>
                <p data-leave-summary-stat="requests" class="mt-2 text-2xl font-black text-slate-900">0</p>
              </div>
              <div class="rounded-2xl border border-white bg-white p-4 shadow-[0_14px_30px_rgba(15,23,42,0.1)]">
                <div class="flex items-center gap-2 text-violet-600">
                  <i class="fa-solid fa-clock text-xs"></i>
                  <p class="text-[10px] font-bold uppercase tracking-wide">Days</p>
                </div>
                <p data-leave-summary-stat="days" class="mt-2 text-2xl font-black text-slate-900">0</p>
              </div>
            </div>
          </div>

          <div class="px-5 pb-6 pt-7 md:px-8">
            <div data-leave-summary-content>
              <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                  <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Type Distribution</p>
                  <h4 class="mt-1 text-lg font-black text-slate-900">How requests are distributed</h4>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ $selectedMonthLabel }}</span>
              </div>
              <div data-leave-summary-list class="space-y-3"></div>
            </div>

            <div data-leave-summary-empty class="hidden overflow-hidden rounded-[1.75rem] border border-emerald-100 bg-[linear-gradient(145deg,#f0fdf4_0%,#f8fafc_55%,#ecfeff_100%)] px-6 py-10 text-center">
              <div class="relative mx-auto h-24 w-24">
                <div class="absolute inset-0 rounded-full bg-emerald-200/40 blur-xl"></div>
                <div class="relative flex h-24 w-24 items-end justify-center gap-2 rounded-[2rem] border border-white bg-white/80 px-5 pb-5 shadow-xl backdrop-blur">
                  <span class="h-6 w-3 rounded-full bg-emerald-300"></span>
                  <span class="h-10 w-3 rounded-full bg-emerald-500"></span>
                  <span class="h-4 w-3 rounded-full bg-cyan-300"></span>
                </div>
              </div>
              <div class="mx-auto mt-6 max-w-md">
                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700">All clear</span>
                <h4 class="mt-3 text-xl font-black text-slate-900">A wonderfully quiet category</h4>
                <p class="mt-2 text-sm leading-6 text-slate-500">There are no request types here for {{ $selectedMonthLabel }}. New activity will appear automatically the moment it arrives.</p>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between md:px-8">
            <p class="text-xs text-slate-400"><i class="fa-solid fa-arrows-rotate mr-1 text-emerald-500"></i> Synced automatically with leave requests</p>
            <button type="button" data-leave-summary-close class="rounded-xl bg-slate-950 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-slate-200 transition hover:-translate-y-0.5 hover:bg-emerald-700">Done</button>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<div id="medical-certificate-zoom-viewer" class="fixed inset-0 z-[130] hidden flex-col bg-slate-950/95" role="dialog" aria-modal="true" aria-label="Medical certificate enlarged viewer">
  <div class="flex flex-wrap items-center justify-center gap-3 border-b border-white/15 bg-slate-900 px-4 py-3">
    <button type="button" data-medical-zoom-out class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-white px-5 py-3 text-base font-bold text-slate-900 transition hover:bg-slate-200">
      <i class="fa-solid fa-magnifying-glass-minus"></i>
      Zoom Out
    </button>
    <button type="button" data-medical-zoom-reset class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-white px-5 py-3 text-base font-bold text-slate-900 transition hover:bg-slate-200">
      <i class="fa-solid fa-rotate-left"></i>
      Reset
    </button>
    <span id="medical-certificate-zoom-level" class="min-w-20 text-center text-lg font-black text-white">100%</span>
    <button type="button" data-medical-zoom-in class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-base font-bold text-white transition hover:bg-blue-500">
      <i class="fa-solid fa-magnifying-glass-plus"></i>
      Zoom In
    </button>
    <button type="button" data-medical-zoom-close class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-base font-bold text-white transition hover:bg-rose-500">
      <i class="fa-solid fa-xmark"></i>
      Close
    </button>
  </div>
  <div id="medical-certificate-zoom-canvas" class="flex-1 overflow-auto p-5 text-center">
    <img id="medical-certificate-zoom-image" src="" alt="" class="mx-auto block max-w-none cursor-zoom-in rounded-lg bg-white shadow-2xl">
  </div>
</div>

<script>
  let medicalCertificateZoom = 1;
  let medicalCertificateBaseWidth = 0;

  const syncLeaveOverlayBodyState = () => {
    const reviewOpen = document.querySelector('[id^="leave-review-modal-"]:not(.hidden)');
    const summaryModal = document.getElementById('leave-summary-types-modal');
    const summaryOpen = summaryModal && !summaryModal.classList.contains('hidden');
    const zoomViewer = document.getElementById('medical-certificate-zoom-viewer');
    const zoomOpen = zoomViewer && !zoomViewer.classList.contains('hidden');
    document.body.classList.toggle('overflow-hidden', Boolean(reviewOpen || summaryOpen || zoomOpen));
  };

  const applyMedicalCertificateZoom = () => {
    const image = document.getElementById('medical-certificate-zoom-image');
    const level = document.getElementById('medical-certificate-zoom-level');
    if (!image || medicalCertificateBaseWidth <= 0) return;

    image.style.width = `${Math.round(medicalCertificateBaseWidth * medicalCertificateZoom)}px`;
    if (level) {
      level.textContent = `${Math.round(medicalCertificateZoom * 100)}%`;
    }
  };

  const resetMedicalCertificateZoom = () => {
    const image = document.getElementById('medical-certificate-zoom-image');
    const canvas = document.getElementById('medical-certificate-zoom-canvas');
    if (!image || !canvas || !image.naturalWidth || !image.naturalHeight) return;

    const availableWidth = Math.max(canvas.clientWidth - 40, 200);
    const availableHeight = Math.max(canvas.clientHeight - 40, 200);
    const fitScale = Math.min(
      availableWidth / image.naturalWidth,
      availableHeight / image.naturalHeight,
      1
    );

    medicalCertificateBaseWidth = image.naturalWidth * fitScale;
    medicalCertificateZoom = 1;
    applyMedicalCertificateZoom();
    canvas.scrollTo({ top: 0, left: 0 });
  };

  const openMedicalCertificateZoom = (button) => {
    const viewer = document.getElementById('medical-certificate-zoom-viewer');
    const image = document.getElementById('medical-certificate-zoom-image');
    if (!viewer || !image || !button?.dataset.imageSrc) return;

    image.src = button.dataset.imageSrc;
    image.alt = button.dataset.imageAlt || 'Enlarged medical certificate';
    viewer.classList.remove('hidden');
    viewer.classList.add('flex');
    syncLeaveOverlayBodyState();

    if (image.complete) {
      resetMedicalCertificateZoom();
    } else {
      image.addEventListener('load', resetMedicalCertificateZoom, { once: true });
    }
    viewer.querySelector('[data-medical-zoom-in]')?.focus();
  };

  const closeMedicalCertificateZoom = () => {
    const viewer = document.getElementById('medical-certificate-zoom-viewer');
    if (!viewer) return;
    viewer.classList.add('hidden');
    viewer.classList.remove('flex');
    syncLeaveOverlayBodyState();
  };

  const closeLeaveReviewModal = (modal) => {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    syncLeaveOverlayBodyState();
  };

  const closeLeaveSummaryModal = () => {
    const modal = document.getElementById('leave-summary-types-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    syncLeaveOverlayBodyState();
  };

  const openLeaveSummaryModal = (button) => {
    const modal = document.getElementById('leave-summary-types-modal');
    const list = modal?.querySelector('[data-leave-summary-list]');
    const content = modal?.querySelector('[data-leave-summary-content]');
    const emptyState = modal?.querySelector('[data-leave-summary-empty]');
    if (!modal || !list || !content || !emptyState) return;

    let details = [];
    try {
      details = JSON.parse(button.dataset.summaryDetails || '[]');
    } catch (error) {
      details = [];
    }

    const title = modal.querySelector('[data-leave-summary-title]');
    const subtitle = modal.querySelector('[data-leave-summary-subtitle]');
    if (title) title.textContent = button.dataset.summaryTitle || 'Leave Requests';
    if (subtitle) subtitle.textContent = button.dataset.summarySubtitle || 'Requests grouped by leave type.';

    const totalRequests = details.reduce((sum, item) => sum + Number(item.count || 0), 0);
    const totalDays = details.reduce((sum, item) => sum + Number(item.days || 0), 0);
    const summaryStats = {
      types: details.length,
      requests: totalRequests,
      days: Number.isInteger(totalDays) ? totalDays : totalDays.toFixed(1),
    };
    modal.querySelectorAll('[data-leave-summary-stat]').forEach((stat) => {
      stat.textContent = summaryStats[stat.dataset.leaveSummaryStat] ?? 0;
    });

    const palette = [
      { color: '#10b981', soft: '#d1fae5', icon: 'fa-calendar-check' },
      { color: '#3b82f6', soft: '#dbeafe', icon: 'fa-briefcase-medical' },
      { color: '#8b5cf6', soft: '#ede9fe', icon: 'fa-calendar-days' },
      { color: '#f59e0b', soft: '#fef3c7', icon: 'fa-clock' },
      { color: '#f43f5e', soft: '#ffe4e6', icon: 'fa-heart-pulse' },
    ];

    list.replaceChildren();
    details.forEach((item, index) => {
      const tone = palette[index % palette.length];
      const requestCount = Number(item.count || 0);
      const percentage = totalRequests > 0 ? Math.round((requestCount / totalRequests) * 100) : 0;
      const days = Number(item.days || 0);

      const row = document.createElement('div');
      row.className = 'group relative overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg';

      const accent = document.createElement('span');
      accent.className = 'absolute bottom-0 left-0 top-0 w-1';
      accent.style.backgroundColor = tone.color;

      const layout = document.createElement('div');
      layout.className = 'flex items-center gap-4';

      const icon = document.createElement('span');
      icon.className = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-base';
      icon.style.backgroundColor = tone.soft;
      icon.style.color = tone.color;
      const iconGlyph = document.createElement('i');
      iconGlyph.className = `fa-solid ${tone.icon}`;
      icon.appendChild(iconGlyph);

      const text = document.createElement('div');
      text.className = 'min-w-0 flex-1';

      const type = document.createElement('p');
      type.className = 'truncate text-sm font-black text-slate-900';
      type.textContent = item.type || 'Leave';

      const meta = document.createElement('p');
      meta.className = 'mt-1 text-xs text-slate-500';
      meta.textContent = `${Number.isInteger(days) ? days : days.toFixed(1)} day(s) across ${requestCount} request(s)`;

      const progressTrack = document.createElement('div');
      progressTrack.className = 'mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100';
      const progress = document.createElement('div');
      progress.className = 'leave-summary-progress h-full rounded-full';
      progress.style.width = `${Math.max(percentage, requestCount > 0 ? 5 : 0)}%`;
      progress.style.backgroundColor = tone.color;
      progress.style.animationDelay = `${index * 70}ms`;
      progressTrack.appendChild(progress);

      const metric = document.createElement('div');
      metric.className = 'shrink-0 text-right';

      const count = document.createElement('span');
      count.className = 'block text-xl font-black text-slate-900';
      count.textContent = requestCount;

      const share = document.createElement('span');
      share.className = 'mt-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400';
      share.textContent = `${percentage}% share`;

      text.append(type, meta, progressTrack);
      metric.append(count, share);
      layout.append(icon, text, metric);
      row.append(accent, layout);
      list.appendChild(row);
    });

    const hasDetails = details.length > 0;
    content.classList.toggle('hidden', !hasDetails);
    emptyState.classList.toggle('hidden', hasDetails);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    syncLeaveOverlayBodyState();
    modal.querySelector('[data-leave-summary-close]')?.focus();
  };

  const applyApprovedHistoryDateFilter = () => {
    const section = document.querySelector('[data-approved-history-section]');
    if (!section) return;

    const records = Array.from(section.querySelectorAll('[data-approved-history-record]'));
    if (!records.length) return;

    const fromInput = section.querySelector('[data-approved-history-from]');
    const toInput = section.querySelector('[data-approved-history-to]');
    const fromDate = fromInput?.value || '';
    const toDate = toInput?.value || '';
    let visibleCount = 0;

    records.forEach((record) => {
      const recordStart = record.dataset.approvedStart || '';
      const recordEnd = record.dataset.approvedEnd || recordStart;
      const matchesFrom = !fromDate || (recordEnd !== '' && recordEnd >= fromDate);
      const matchesTo = !toDate || (recordStart !== '' && recordStart <= toDate);
      const isVisible = matchesFrom && matchesTo;

      record.classList.toggle('hidden', !isVisible);
      if (isVisible) visibleCount += 1;
    });

    const countLabel = section.querySelector('[data-approved-history-count]');
    if (countLabel) {
      countLabel.textContent = `Showing ${visibleCount} of ${records.length} approved request(s)`;
    }

    const emptyState = section.querySelector('[data-approved-history-filter-empty]');
    if (emptyState) emptyState.classList.toggle('hidden', visibleCount > 0);

    const historyList = section.querySelector('[data-approved-history-list]');
    if (historyList) historyList.scrollTop = 0;
  };

  document.addEventListener('change', (event) => {
    const fromInput = event.target.closest('[data-approved-history-from]');
    const toInput = event.target.closest('[data-approved-history-to]');
    if (!fromInput && !toInput) return;

    const section = event.target.closest('[data-approved-history-section]');
    const currentFrom = section?.querySelector('[data-approved-history-from]');
    const currentTo = section?.querySelector('[data-approved-history-to]');
    if (currentFrom?.value && currentTo?.value && currentFrom.value > currentTo.value) {
      if (fromInput) {
        currentTo.value = currentFrom.value;
      } else {
        currentFrom.value = currentTo.value;
      }
    }
    applyApprovedHistoryDateFilter();
  });

  document.addEventListener('click', (event) => {
    const historyResetButton = event.target.closest('[data-approved-history-reset]');
    if (historyResetButton) {
      const section = historyResetButton.closest('[data-approved-history-section]');
      const fromInput = section?.querySelector('[data-approved-history-from]');
      const toInput = section?.querySelector('[data-approved-history-to]');
      if (fromInput) fromInput.value = '';
      if (toInput) toInput.value = '';
      applyApprovedHistoryDateFilter();
      return;
    }

    const summaryButton = event.target.closest('[data-leave-summary-open]');
    if (summaryButton) {
      openLeaveSummaryModal(summaryButton);
      return;
    }

    const summaryModal = document.getElementById('leave-summary-types-modal');
    if (event.target.closest('[data-leave-summary-close]') || event.target === summaryModal) {
      closeLeaveSummaryModal();
      return;
    }

    const imageZoomButton = event.target.closest('[data-medical-image-zoom]');
    if (imageZoomButton) {
      openMedicalCertificateZoom(imageZoomButton);
      return;
    }

    if (event.target.closest('[data-medical-zoom-close]')) {
      closeMedicalCertificateZoom();
      return;
    }

    if (event.target.closest('[data-medical-zoom-in]') || event.target.id === 'medical-certificate-zoom-image') {
      medicalCertificateZoom = Math.min(medicalCertificateZoom + 0.5, 5);
      applyMedicalCertificateZoom();
      return;
    }

    if (event.target.closest('[data-medical-zoom-out]')) {
      medicalCertificateZoom = Math.max(medicalCertificateZoom - 0.5, 0.5);
      applyMedicalCertificateZoom();
      return;
    }

    if (event.target.closest('[data-medical-zoom-reset]')) {
      resetMedicalCertificateZoom();
      return;
    }

    const openButton = event.target.closest('[data-leave-review-open]');
    if (openButton) {
      const modal = document.getElementById(openButton.dataset.leaveReviewOpen);
      if (!modal) return;

      document.body.appendChild(modal);
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      syncLeaveOverlayBodyState();
      modal.querySelector('[data-leave-review-close]')?.focus();
      return;
    }

    const closeButton = event.target.closest('[data-leave-review-close]');
    if (closeButton) {
      closeLeaveReviewModal(closeButton.closest('[role="dialog"]'));
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const summaryModal = document.getElementById('leave-summary-types-modal');
    if (summaryModal && !summaryModal.classList.contains('hidden')) {
      closeLeaveSummaryModal();
      return;
    }
    const zoomViewer = document.getElementById('medical-certificate-zoom-viewer');
    if (zoomViewer && !zoomViewer.classList.contains('hidden')) {
      closeMedicalCertificateZoom();
      return;
    }
    closeLeaveReviewModal(document.querySelector('[id^="leave-review-modal-"]:not(.hidden)'));
  });

  const initLeaveManagementAnimation = () => {
    const page = document.getElementById('leave-management-page');
    if (!page) return;

    const revealItems = Array.from(page.querySelectorAll('.leave-management-reveal'));
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
      threshold: 0.01,
      rootMargin: '0px 0px -5% 0px',
    });

    revealItems.forEach((item) => observer.observe(item));
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLeaveManagementAnimation, { once: true });
  } else {
    initLeaveManagementAnimation();
  }

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

  @php
    $leaveManagementSnapshotUrl = route('admin.leaveManagement.snapshot', array_filter([
      'month' => $selectedMonth ?? now()->format('Y-m'),
      'tab_session' => request()->query('tab_session'),
    ]));
  @endphp
  const leaveManagementSnapshotUrl = @json($leaveManagementSnapshotUrl);
  let leaveManagementSnapshotToken = @json($leaveSnapshotToken ?? '');
  let leaveManagementRefreshPending = false;

  const silentlyRefreshLeaveManagement = async () => {
    const response = await fetch(window.location.href, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/html',
      },
      credentials: 'same-origin',
      cache: 'no-store',
    });
    if (!response.ok) return false;

    const html = await response.text();
    const incomingDocument = new DOMParser().parseFromString(html, 'text/html');
    const incomingPage = incomingDocument.getElementById('leave-management-page');
    const currentPage = document.getElementById('leave-management-page');
    if (!incomingPage || !currentPage) return false;

    const scrollX = window.scrollX;
    const scrollY = window.scrollY;
    const nextPage = document.importNode(incomingPage, true);
    nextPage.querySelectorAll('.leave-management-reveal').forEach((item) => {
      item.classList.add('is-visible');
    });
    document.querySelectorAll('body > [id^="leave-review-modal-"]').forEach((modal) => modal.remove());
    currentPage.replaceWith(nextPage);

    const incomingHeader = incomingDocument.querySelector('[data-admin-scroll-header]');
    const currentHeader = document.querySelector('[data-admin-scroll-header]');
    if (incomingHeader && currentHeader) {
      const nextHeader = document.importNode(incomingHeader, true);
      nextHeader.classList.toggle('is-scrolled', window.scrollY > 24);
      currentHeader.replaceWith(nextHeader);
    }

    requestAnimationFrame(() => window.scrollTo(scrollX, scrollY));
    return true;
  };

  const refreshLeaveManagementWhenChanged = async () => {
    if (leaveManagementRefreshPending || document.hidden) return;
    if (document.querySelector('[id^="leave-review-modal-"]:not(.hidden)')) return;
    const summaryModal = document.getElementById('leave-summary-types-modal');
    if (summaryModal && !summaryModal.classList.contains('hidden')) return;

    const zoomViewer = document.getElementById('medical-certificate-zoom-viewer');
    if (zoomViewer && !zoomViewer.classList.contains('hidden')) return;

    try {
      const response = await fetch(leaveManagementSnapshotUrl, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) return;

      const snapshot = await response.json();
      if (!snapshot.token || snapshot.token === leaveManagementSnapshotToken) return;

      leaveManagementRefreshPending = true;
      const refreshed = await silentlyRefreshLeaveManagement();
      if (refreshed) {
        leaveManagementSnapshotToken = snapshot.token;
      }
    } catch (error) {
      // Keep the current page usable and retry on the next polling interval.
    } finally {
      leaveManagementRefreshPending = false;
    }
  };

  window.setInterval(refreshLeaveManagementWhenChanged, 5000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) refreshLeaveManagementWhenChanged();
  });
</script>
</body>
</html>
