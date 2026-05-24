@php
  $isHiringRoute = request()->routeIs('admin.adminApplicant')
    || request()->routeIs('admin.adminPosition')
    || request()->routeIs('admin.adminInterview');
  $adminUser = auth()->user();
  $nameParts = array_filter([
    trim((string) ($adminUser->first_name ?? '')),
    trim((string) ($adminUser->middle_name ?? '')),
    trim((string) ($adminUser->last_name ?? '')),
  ]);
  $adminDisplayName = count($nameParts) ? implode(' ', $nameParts) : (string) ($adminUser->email ?? 'Admin');
  $initialA = strtoupper(substr(trim((string) ($adminUser->first_name ?? 'A')), 0, 1));
  $initialB = strtoupper(substr(trim((string) ($adminUser->last_name ?? '')), 0, 1));
  $adminInitials = trim($initialA.$initialB);
  if ($adminInitials === '') {
    $adminInitials = 'AD';
  }
  $adminRoleLabel = trim((string) ($adminUser->role ?? 'Admin'));
  $tabSession = trim((string) request()->query('tab_session', ''));
  $adminUnreadMessages = 0;
  $adminPendingLeaveCount = 0;
  $adminPendingApplicantCount = 0;
  $adminSidebarNotificationCount = (int) data_get($adminNotificationStats ?? [], 'total', 0);
  $hasEmployeeMissingInfoAlert = false;
  $employeeMissingInfoCount = 0;
  $employeeMissingRequiredDocumentsByApplicant = collect();
  if (
    $adminUser
    && \Illuminate\Support\Facades\Schema::hasTable('conversations')
    && \Illuminate\Support\Facades\Schema::hasTable('conversation_messages')
  ) {
    $adminUnreadMessages = \App\Models\ConversationMessage::query()
      ->whereNull('read_at')
      ->where('sender_user_id', '!=', (int) $adminUser->id)
      ->whereHas('conversation', function ($query) use ($adminUser) {
        $query->where(function ($innerQuery) use ($adminUser) {
          $innerQuery->where('user_one_id', (int) $adminUser->id)
            ->orWhere('user_two_id', (int) $adminUser->id);
        });
      })
      ->count();
  }
  if (\Illuminate\Support\Facades\Schema::hasTable('leave_applications')) {
    $adminPendingLeaveCount = \App\Models\LeaveApplication::query()
      ->where(function ($query) {
        $query->whereNull('status')
          ->orWhereRaw("TRIM(status) = ''")
          ->orWhereRaw("LOWER(TRIM(status)) = ?", ['pending']);
      })
      ->count();
  }
  if ($adminSidebarNotificationCount <= 0 && !isset($adminNotificationStats)) {
    $adminPendingResignationCount = 0;
    $adminHiringNotificationCount = 0;

    if (\Illuminate\Support\Facades\Schema::hasTable('applicants')) {
      $adminHiringNotificationCount = \App\Models\Applicant::query()->exists() ? 1 : 0;
    }

    if (\Illuminate\Support\Facades\Schema::hasTable('resignations')) {
      $adminPendingResignationCount = \App\Models\Resignation::query()
        ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['pending'])
        ->count();
    }

    $adminSidebarNotificationCount = $adminPendingLeaveCount
      + $adminHiringNotificationCount
      + $adminPendingResignationCount;
  }
  if (\Illuminate\Support\Facades\Schema::hasTable('applicants')) {
    $adminPendingApplicantCount = \App\Models\Applicant::query()
      ->where(function ($query) {
        $query->whereNull('application_status')
          ->orWhereRaw("TRIM(application_status) = ''")
          ->orWhereRaw("LOWER(TRIM(application_status)) = ?", ['pending']);
      })
      ->count();
  }
  if (
    \Illuminate\Support\Facades\Schema::hasTable('users')
    && \Illuminate\Support\Facades\Schema::hasTable('employees')
    && \Illuminate\Support\Facades\Schema::hasTable('applicants')
    && \Illuminate\Support\Facades\Schema::hasTable('licenses')
    && \Illuminate\Support\Facades\Schema::hasTable('governments')
    && \Illuminate\Support\Facades\Schema::hasTable('salaries')
  ) {
    $isMissingEmployeeValue = static function ($value): bool {
      if (is_null($value)) {
        return true;
      }
      if (is_string($value)) {
        return trim($value) === '';
      }
      return false;
    };

    $normalizeEmployeeDocumentLabel = static function (string $value): string {
      $normalized = strtolower(trim($value));
      if ($normalized === '') {
        return '';
      }

      return preg_replace('/\s+/', ' ', $normalized);
    };

    $employeeAlertCandidates = \App\Models\User::query()
      ->with([
        'employee:id,user_id,account_number,sex,civil_status,contact_number,birthday,address',
        'applicant:applicants.id,applicants.user_id,applicants.phone,applicants.address',
        'license:id,user_id,license,registration_number',
        'government:id,user_id,SSS,TIN,PhilHealth,MID,RTN',
        'salary:id,user_id,salary',
      ])
      ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = ?", ['employee'])
      ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['approved'])
      ->get();

    if (
      \Illuminate\Support\Facades\Schema::hasTable('applicant_documents')
      && $employeeAlertCandidates->isNotEmpty()
    ) {
      $defaultRequiredDocuments = collect([
        'Resume/CV',
        'Cover Letter',
        'Personal Data Sheet',
        'Transcript Of Records',
        'Diploma',
        'PRC License/Board Rating',
        'Certificate Of Eligibility / Certificate of Passing',
        'Certifications & Supporting Document',
        'Membership/Affiliation',
      ]);

      $employeeApplicantIds = $employeeAlertCandidates
        ->pluck('applicant.id')
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

      if ($employeeApplicantIds->isNotEmpty()) {
        $employeeUploadedDocumentTypesByApplicant = \App\Models\ApplicantDocument::query()
          ->select(['applicant_id', 'type', 'filename'])
          ->whereIn('applicant_id', $employeeApplicantIds)
          ->where('type', 'not like', '__REQUIRED__::%')
          ->where('type', '!=', '__NOTICE__')
          ->where('type', '!=', '__FOLDER__')
          ->get()
          ->groupBy('applicant_id')
          ->map(function ($documents) use ($normalizeEmployeeDocumentLabel) {
            return $documents
              ->map(fn ($doc) => $normalizeEmployeeDocumentLabel((string) ($doc->type ?: $doc->filename)))
              ->filter()
              ->unique()
              ->values();
          });

        $employeeRequiredDocumentsByApplicant = \App\Models\ApplicantDocument::query()
          ->select(['applicant_id', 'type'])
          ->whereIn('applicant_id', $employeeApplicantIds)
          ->where('type', 'like', '__REQUIRED__::%')
          ->get()
          ->groupBy('applicant_id')
          ->map(function ($documents) {
            return $documents
              ->map(fn ($doc) => trim((string) substr((string) ($doc->type ?? ''), strlen('__REQUIRED__::'))))
              ->filter()
              ->unique(fn ($value) => strtolower($value))
              ->values();
          });

        $employeeMissingRequiredDocumentsByApplicant = $employeeApplicantIds
          ->mapWithKeys(function ($applicantId) use (
            $defaultRequiredDocuments,
            $employeeRequiredDocumentsByApplicant,
            $employeeUploadedDocumentTypesByApplicant,
            $normalizeEmployeeDocumentLabel
          ) {
            $requiredDocuments = $employeeRequiredDocumentsByApplicant->get($applicantId, $defaultRequiredDocuments)
              ->map(fn ($item) => trim((string) $item))
              ->filter()
              ->values();

            $uploadedDocuments = $employeeUploadedDocumentTypesByApplicant->get($applicantId, collect());
            $missingCount = $requiredDocuments
              ->filter(function ($required) use ($uploadedDocuments, $normalizeEmployeeDocumentLabel) {
                return !$uploadedDocuments->contains($normalizeEmployeeDocumentLabel((string) $required));
              })
              ->count();

            return [$applicantId => $missingCount];
          });
      }
    }

    $employeeMissingInfoCount = $employeeAlertCandidates->filter(function ($emp) use ($isMissingEmployeeValue, $employeeMissingRequiredDocumentsByApplicant) {
      return collect([
        data_get($emp, 'employee.account_number'),
        data_get($emp, 'employee.sex'),
        data_get($emp, 'employee.civil_status'),
        data_get($emp, 'employee.contact_number') ?: data_get($emp, 'applicant.phone'),
        data_get($emp, 'employee.birthday'),
        data_get($emp, 'employee.address') ?: data_get($emp, 'applicant.address'),
        data_get($emp, 'license.license'),
        data_get($emp, 'license.registration_number'),
        data_get($emp, 'government.SSS'),
        data_get($emp, 'government.TIN'),
        data_get($emp, 'government.PhilHealth'),
        data_get($emp, 'government.MID'),
        data_get($emp, 'government.RTN'),
        data_get($emp, 'salary.salary'),
      ])->contains(fn ($value) => $isMissingEmployeeValue($value))
        || (int) $employeeMissingRequiredDocumentsByApplicant->get((int) data_get($emp, 'applicant.id'), 0) > 0;
    })->count();

    $hasEmployeeMissingInfoAlert = $employeeMissingInfoCount > 0;
  }
@endphp

<style>
  [x-cloak]{display:none !important;}
  .admin-sidebar {
    background: linear-gradient(180deg, #020617 0%, #111827 54%, #064e3b 100%);
    box-shadow: 8px 0 24px rgba(2, 6, 23, 0.4);
  }
  .admin-sidebar nav a,
  .admin-sidebar nav summary {
    min-height: 44px;
  }
  .admin-sidebar nav a > i,
  .admin-sidebar nav summary > span > i {
    width: 1.25rem;
    text-align: center;
    flex-shrink: 0;
  }
  details.hiring-menu > summary { list-style: none; }
  details.hiring-menu > summary::-webkit-details-marker { display: none; }
  details.hiring-menu[open] .hiring-chevron { transform: rotate(180deg); }
  details.more-menu > summary { list-style: none; }
  details.more-menu > summary::-webkit-details-marker { display: none; }
  details.more-menu[open] .more-chevron { transform: rotate(180deg); }
  details.matrix-menu > summary { list-style: none; }
  details.matrix-menu > summary::-webkit-details-marker { display: none; }
  details.matrix-menu[open] .matrix-chevron { transform: rotate(90deg); }
  .admin-sidebar-alert-dot {
    position: absolute;
    top: -0.3rem;
    right: -0.35rem;
    display: inline-flex;
    height: 0.95rem;
    min-width: 0.95rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: linear-gradient(135deg, #fb7185 0%, #ef4444 100%);
    color: #fff;
    font-size: 0.58rem;
    font-weight: 800;
    line-height: 1;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.92);
    animation: admin-sidebar-alert-pulse 1.4s ease-in-out infinite;
  }
  .admin-sidebar-alert-dot::after {
    content: '';
    position: absolute;
    inset: -0.15rem;
    border-radius: inherit;
    border: 2px solid rgba(251, 113, 133, 0.45);
    animation: admin-sidebar-alert-ring 1.4s ease-out infinite;
  }
  @keyframes admin-sidebar-alert-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
  }
  @keyframes admin-sidebar-alert-ring {
    0% { opacity: 0.7; transform: scale(0.92); }
    100% { opacity: 0; transform: scale(1.55); }
  }
  .admin-sidebar-count-badge {
    display: none;
  }
  .admin-sidebar:hover .admin-sidebar-count-badge,
  .admin-sidebar.is-open .admin-sidebar-count-badge {
    display: inline-flex;
  }

  .admin-sidebar-overlay {
    position: fixed;
    inset: 0;
    z-index: 45;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    opacity: 0;
    pointer-events: none;
    transition: opacity 180ms ease;
  }

  .admin-sidebar-overlay.is-visible {
    opacity: 1;
    pointer-events: auto;
  }

  @media (max-width: 1024px) {
    .admin-sidebar {
      width: 18rem !important;
      transform: translateX(-100%);
      transition: transform 220ms ease;
    }

    .admin-sidebar.is-open {
      transform: translateX(0);
    }

    .admin-sidebar ~ main {
      margin-left: 0 !important;
      width: 100%;
    }

    .admin-sidebar .admin-sidebar-text {
      max-width: 100% !important;
      opacity: 1 !important;
    }
  }

  @media (min-width: 1025px) {
    [data-admin-sidebar-toggle],
    [data-admin-sidebar-overlay] {
      display: none !important;
    }

    .admin-sidebar {
      transform: none !important;
    }
  }
</style>

<button
  type="button"
  data-admin-sidebar-toggle
  class="fixed left-4 top-4 z-[70] inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-200 bg-white/95 text-emerald-700 shadow-lg shadow-slate-900/10 backdrop-blur"
  aria-label="Open admin menu"
  aria-controls="admin-sidebar"
  aria-expanded="false"
>
  <i class="fa-solid fa-bars"></i>
</button>

<div data-admin-sidebar-overlay class="admin-sidebar-overlay"></div>

<aside id="admin-sidebar" class="admin-sidebar group fixed left-0 top-0 h-screen text-slate-200 flex flex-col w-16 hover:w-72 transition-all duration-300 overflow-x-hidden overflow-y-auto z-50">
  <div class="px-3 py-4 flex items-center gap-3 border-b border-slate-800/90">
    <div class="flex items-center justify-center">
      <!-- Small square icon visible when collapsed -->
      <img src="{{ asset('images/logo.webp') }}" alt="HR Logo" class="w-8 h-8 object-contain block group-hover:hidden">
      <!-- Full logo visible when sidebar expanded -->
      <img src="{{ asset('images/logo.webp') }}" alt="HR Logo Full" class="hidden group-hover:block h-16">
    </div>
    <span class="admin-sidebar-text text-lg font-semibold inline-block whitespace-nowrap max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300 ml-2">HR PORTAL</span>
  </div>

  <nav class="flex-1 px-2 group-hover:px-3 py-4 space-y-1.5">

    <!-- Dashboard -->
    <a href="{{ route('admin.adminHome', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
       data-admin-nav
       class="flex items-center gap-0 group-hover:gap-3 px-4 py-2.5 rounded-lg font-medium transition justify-center group-hover:justify-start
       {{ request()->routeIs('admin.adminHome')
        ? 'bg-green-600 text-white'
         : 'text-white hover:bg-green-600/30' }}">
      <span class="relative inline-flex w-5 items-center justify-center">
        <i class="fa-solid fa-house"></i>
        @if ($adminSidebarNotificationCount > 0)
          <span class="admin-sidebar-alert-dot group-hover:hidden" aria-hidden="true">!</span>
        @endif
      </span>
      <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Dashboard</span>
      @if ($adminSidebarNotificationCount > 0)
        <span class="admin-sidebar-count-badge ml-auto min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">
          {{ $adminSidebarNotificationCount > 99 ? '99+' : $adminSidebarNotificationCount }}
        </span>
      @endif
    </a>

    <!-- Employees -->
    <a href="{{ route('admin.adminEmployee', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
       data-admin-nav
       class="flex items-center gap-0 group-hover:gap-3 px-4 py-2.5 rounded-lg font-medium transition justify-center group-hover:justify-start
       {{ request()->routeIs('admin.adminEmployee')
         ? 'bg-green-600 text-white'
         : 'text-white hover:bg-green-600/30' }}">
      <span class="relative inline-flex w-5 items-center justify-center">
        <i class="fa-solid fa-users"></i>
        @if ($hasEmployeeMissingInfoAlert)
          <span class="admin-sidebar-alert-dot group-hover:hidden" aria-hidden="true">!</span>
        @endif
      </span>
      <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Employees</span>
      @if ($employeeMissingInfoCount > 0)
        <span class="admin-sidebar-count-badge ml-auto min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">
          {{ $employeeMissingInfoCount > 99 ? '99+' : $employeeMissingInfoCount }}
        </span>
      @endif
    </a>

    <!-- Attendance -->
    <a href="{{ route('admin.adminAttendance', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
       data-admin-nav
       class="flex items-center gap-0 group-hover:gap-3 px-4 py-2.5 rounded-lg font-medium transition justify-center group-hover:justify-start
       {{ request()->routeIs('admin.adminAttendance')
        ? 'bg-green-600 text-white'
        : 'text-white hover:bg-green-600/30' }}">
      <i class="fa-solid fa-calendar-check"></i>
      <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Attendance</span>
    </a>

    <!-- Leave -->
    <a href="{{ route('admin.adminLeaveManagement', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
       data-admin-nav
       class="flex items-center gap-0 group-hover:gap-3 px-4 py-2.5 rounded-lg font-medium transition justify-center group-hover:justify-start
       {{ request()->routeIs('admin.adminLeaveManagement')
          ? 'bg-green-600 text-white'
          : 'text-white hover:bg-green-600/30' }}">
      <span class="relative inline-flex w-5 items-center justify-center">
        <i class="fa-solid fa-clipboard"></i>
        @if ($adminPendingLeaveCount > 0)
          <span class="admin-sidebar-alert-dot group-hover:hidden" aria-hidden="true">!</span>
        @endif
      </span>
      <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Leave Management</span>
      @if ($adminPendingLeaveCount > 0)
        <span class="admin-sidebar-count-badge ml-auto min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">
          {{ $adminPendingLeaveCount > 99 ? '99+' : $adminPendingLeaveCount }}
        </span>
      @endif
    </a>

    <!-- ✅ Hiring Dropdown (FIXED) -->
    <!-- Payslip -->
    <a href="{{ route('admin.adminPayslip', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
       data-admin-nav
       class="flex items-center gap-0 group-hover:gap-3 px-4 py-2.5 rounded-lg font-medium transition justify-center group-hover:justify-start
       {{ request()->routeIs('admin.adminPayslip')
        ? 'bg-green-600 text-white'
        : 'text-white hover:bg-green-600/30' }}">
      <i class="fa-solid fa-file-invoice-dollar"></i>
      <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Payslip</span>
    </a>

    <a href="{{ route('admin.adminCommunication', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
       data-admin-nav
       class="relative flex items-center gap-0 group-hover:gap-3 px-4 py-2.5 rounded-lg font-medium transition justify-center group-hover:justify-start
       {{ request()->routeIs('admin.adminCommunication')
          ? 'bg-green-600 text-white'
          : 'text-white hover:bg-green-600/30' }}">
      <span class="relative inline-flex items-center justify-center">
        <i class="fa-solid fa-comments"></i>
        @if ($adminUnreadMessages > 0)
          <span class="absolute -right-2 -top-2 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold leading-none text-white group-hover:hidden">{{ $adminUnreadMessages > 9 ? '9+' : $adminUnreadMessages }}</span>
        @endif
      </span>
      <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Communication</span>
      @if ($adminUnreadMessages > 0)
        <span class="admin-sidebar-count-badge ml-auto min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">{{ $adminUnreadMessages > 99 ? '99+' : $adminUnreadMessages }}</span>
      @endif
    </a>

    <details class="space-y-1 hiring-menu" {{ $isHiringRoute ? 'open' : '' }}>
      <summary
        class="w-full flex items-center justify-center group-hover:justify-between px-4 py-2.5 rounded-lg font-medium transition text-white hover:bg-green-600/30 cursor-pointer"
      >
        <span class="flex items-center gap-0 group-hover:gap-3 justify-center group-hover:justify-start">
          <span class="relative inline-flex items-center justify-center">
            <i class="fa-solid fa-briefcase"></i>
            @if ($adminPendingApplicantCount > 0)
              <span class="admin-sidebar-alert-dot group-hover:hidden" aria-hidden="true">!</span>
            @endif
          </span>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Hiring</span>
        </span>
        @if ($adminPendingApplicantCount > 0)
          <span class="admin-sidebar-count-badge ml-auto min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">
            {{ $adminPendingApplicantCount > 99 ? '99+' : $adminPendingApplicantCount }}
          </span>
        @endif

        <i class="fa-solid fa-chevron-down hidden group-hover:inline-block transition-all duration-200 hiring-chevron"></i>
      </summary>

      <!-- Submenu -->
      <div class="ml-0 group-hover:ml-8 space-y-1">

        <a href="{{ route('admin.adminApplicant', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
           data-admin-nav
           class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
           {{ request()->routeIs('admin.adminApplicant')
                ? 'bg-green-600 text-white'
                : 'text-white hover:bg-green-600/30' }}">
          <span class="relative inline-flex items-center justify-center">
            <i class="fa-solid fa-user-check"></i>
            @if ($adminPendingApplicantCount > 0)
              <span class="admin-sidebar-alert-dot group-hover:hidden" aria-hidden="true">!</span>
            @endif
          </span>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Applicant</span>
          @if ($adminPendingApplicantCount > 0)
            <span class="admin-sidebar-count-badge ml-auto min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">
              {{ $adminPendingApplicantCount > 99 ? '99+' : $adminPendingApplicantCount }}
            </span>
          @endif
        </a>

        <a href="{{ route('admin.adminPosition', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
           data-admin-nav
           class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
           {{ request()->routeIs('admin.adminPosition')
                ? 'bg-green-600 text-white'
                : 'text-white hover:bg-green-600/30' }}">
          <i class="fa-solid fa-briefcase"></i>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Job Position</span>
        </a>

        <a href="{{ route('admin.adminInterview', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
           data-admin-nav
           class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
           {{ request()->routeIs('admin.adminInterview')
                ? 'bg-green-600 text-white'
                : 'text-white hover:bg-green-600/30' }}">
          <i class="fa-solid fa-comments"></i>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Interview</span>
        </a>

      </div>
    </details>

    @if (false)
      <!-- Reports hidden temporarily -->
      <a href="{{ route('admin.adminReports') }}"
         class="flex items-center gap-0 group-hover:gap-3 px-4 py-2.5 rounded-lg font-medium transition justify-center group-hover:justify-start
         {{ request()->routeIs('admin.adminReports')
          ? 'bg-green-600 text-white'
          : 'text-white hover:bg-green-600/30' }}">
        <i class="fa-solid fa-chart-line"></i>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Reports</span>
      </a>
    @endif

    <!-- See More -->
    <details class="space-y-1 more-menu">
      <summary
        class="w-full flex items-center justify-center group-hover:justify-between px-4 py-2.5 rounded-lg font-medium transition text-white hover:bg-green-600/30 cursor-pointer"
      >
        <span class="flex items-center gap-0 group-hover:gap-3 justify-center group-hover:justify-start">
          <i class="fa-solid fa-ellipsis"></i>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">More</span>
        </span>
        <i class="fa-solid fa-chevron-down hidden group-hover:inline-block transition-all duration-200 more-chevron"></i>
      </summary>

      <div class="ml-0 group-hover:ml-8 space-y-1">
        <a href="{{ route('admin.adminCalendar', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
           data-admin-nav
           class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
           {{ request()->routeIs('admin.adminCalendar')
                ? 'bg-green-600 text-white'
                : 'text-white hover:bg-green-600/30' }}">
          <i class="fa-solid fa-calendar-days"></i>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Calendar</span>
        </a>

        <a href="{{ route('admin.adminLoads', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
           data-admin-nav
           class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
           {{ request()->routeIs('admin.adminLoads')
                ? 'bg-green-600 text-white'
                : 'text-white hover:bg-green-600/30' }}">
          <i class="fa-solid fa-book-open-reader"></i>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Loads</span>
        </a>

        <details class="space-y-1 matrix-menu">
          <summary
            class="w-full flex items-center justify-center group-hover:justify-between px-4 py-2 rounded-md text-sm transition text-white hover:bg-green-600/30 cursor-pointer"
          >
            <span class="flex items-center gap-0 group-hover:gap-2 justify-center group-hover:justify-start">
              <i class="fa-solid fa-folder"></i>
              <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Matrix</span>
            </span>
            <i class="fa-solid fa-chevron-right hidden group-hover:inline-block transition-all duration-200 matrix-chevron"></i>
          </summary>

          <div class="ml-0 group-hover:ml-6 space-y-1">
            <a href="{{ route('admin.schoolAdministrator', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
               data-admin-nav
               class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
               {{ request()->routeIs('admin.schoolAdministrator')
                    ? 'bg-green-600 text-white'
                    : 'text-white hover:bg-green-600/30' }}">
              <i class="fa-regular fa-file"></i>
              <span class="admin-sidebar-text hidden group-hover:block leading-tight break-words">School Administrator</span>
            </a>

            <a href="{{ route('admin.nonTeachingMatrix', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
               data-admin-nav
               class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
               {{ request()->routeIs('admin.nonTeachingMatrix')
                    ? 'bg-green-600 text-white'
                    : 'text-white hover:bg-green-600/30' }}">
              <i class="fa-regular fa-file"></i>
              <span class="admin-sidebar-text hidden group-hover:block leading-tight break-words">Academic Non-Teaching</span>
            </a>

            <a href="{{ route('admin.teachingMatrix', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
               data-admin-nav
               class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
               {{ request()->routeIs('admin.teachingMatrix')
                    ? 'bg-green-600 text-white'
                    : 'text-white hover:bg-green-600/30' }}">
              <i class="fa-regular fa-file"></i>
              <span class="admin-sidebar-text hidden group-hover:block leading-tight break-words">Academic Staff / Teaching</span>
            </a>
          </div>
        </details>

        <a href="{{ route('admin.adminResignations', $tabSession !== '' ? ['tab_session' => $tabSession] : []) }}"
           data-admin-nav
           class="flex items-center gap-0 group-hover:gap-2 px-4 py-2 rounded-md text-sm justify-center group-hover:justify-start
           {{ request()->routeIs('admin.adminResignations')
                ? 'bg-green-600 text-white'
                : 'text-white hover:bg-green-600/30' }}">
          <i class="fa-solid fa-user-minus"></i>
          <span class="admin-sidebar-text whitespace-nowrap inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">Resignations</span>
        </a>

      </div>
    </details>

  </nav>

  <!-- Profile -->
  <div class="relative px-3 group-hover:px-6 py-4 border-t border-slate-800/90 flex items-center gap-3 justify-center group-hover:justify-start">
    <div class="w-9 h-9 min-w-9 min-h-9 max-w-9 max-h-9 shrink-0 rounded-full bg-emerald-500 flex items-center justify-center text-white font-semibold leading-none">{{ $adminInitials }}</div>
    <div class="admin-sidebar-text text-sm inline-block max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300">
      <div class="flex items-center gap-2">
        <p class="font-medium truncate">{{ $adminDisplayName }}</p>
        <button
          type="button"
          data-admin-profile-menu-toggle
          class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 text-slate-200 transition hover:border-emerald-400 hover:bg-slate-800 hover:text-white"
          title="Settings"
          aria-label="Open settings menu"
          aria-expanded="false"
        >
          <i class="fa-solid fa-gear"></i>
        </button>
      </div>
      <p class="text-slate-400">{{ $adminRoleLabel }}</p>
    </div>

    <div
      data-admin-profile-menu
      class="absolute bottom-20 left-4 right-4 hidden overflow-hidden rounded-2xl border border-slate-700 bg-slate-950/95 p-2 text-sm shadow-2xl shadow-slate-950/40"
    >
      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-slate-200 transition hover:bg-slate-800 hover:text-white">
        <i class="fa-solid fa-gear w-4 text-center text-emerald-300"></i>
        <span>Settings</span>
      </button>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        @if($tabSession !== '')
          <input type="hidden" name="tab_session" value="{{ $tabSession }}">
        @endif
        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-slate-200 transition hover:bg-rose-600 hover:text-white">
          <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
          <span>Logout</span>
        </button>
      </form>
    </div>
  </div>
</aside>

<style>
  .admin-nav-overlay {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(2, 6, 23, 0.24);
    backdrop-filter: blur(6px);
    opacity: 0;
    pointer-events: none;
    transition: opacity 180ms ease;
  }

  .admin-nav-overlay.is-visible {
    opacity: 1;
    pointer-events: auto;
  }

  .admin-nav-overlay__card {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.95rem 1.15rem;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.94);
    color: #f8fafc;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.28);
    transform: translateY(8px) scale(0.98);
    transition: transform 220ms ease;
  }

  .admin-nav-overlay.is-visible .admin-nav-overlay__card {
    transform: translateY(0) scale(1);
  }

  .admin-nav-overlay__spinner {
    width: 1rem;
    height: 1rem;
    border-radius: 9999px;
    border: 2px solid rgba(255, 255, 255, 0.28);
    border-top-color: #4ade80;
    animation: admin-nav-spin 0.75s linear infinite;
  }

  @keyframes admin-nav-spin {
    to { transform: rotate(360deg); }
  }
</style>

<script>
  (function () {
    const links = Array.from(document.querySelectorAll('[data-admin-nav]'));
    const sidebar = document.querySelector('.admin-sidebar');
    const sidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');
    const sidebarOverlay = document.querySelector('[data-admin-sidebar-overlay]');
    const profileMenuToggle = document.querySelector('[data-admin-profile-menu-toggle]');
    const profileMenu = document.querySelector('[data-admin-profile-menu]');
    const currentUrl = new URL(window.location.href);
    const tabSession = currentUrl.searchParams.get('tab_session') || '';
    const adminNotificationUnreadKey = 'admin_notifications_unread_v1';
    localStorage.setItem(adminNotificationUnreadKey, '0');
    if (!links.length && !tabSession && !sidebarToggle && !profileMenuToggle) {
      return;
    }

    let overlay = document.querySelector('[data-admin-nav-overlay]');
    const prefetched = new Set();

    const appendTabSession = (href) => {
      if (!href || !tabSession) {
        return href;
      }

      const url = new URL(href, window.location.origin);
      if (url.origin !== window.location.origin) {
        return href;
      }

      url.searchParams.set('tab_session', tabSession);
      return `${url.pathname}${url.search}${url.hash}`;
    };

    if (tabSession) {
      document.querySelectorAll('a[href]').forEach((anchor) => {
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
          return;
        }

        const nextHref = appendTabSession(href);
        if (nextHref) {
          anchor.setAttribute('href', nextHref);
        }
      });

      document.querySelectorAll('form').forEach((form) => {
        let hiddenInput = form.querySelector('input[name="tab_session"]');
        if (!hiddenInput) {
          hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'tab_session';
          form.appendChild(hiddenInput);
        }
        hiddenInput.value = tabSession;
      });
    }

    const ensureOverlay = () => {
      if (overlay) {
        return overlay;
      }

      overlay = document.createElement('div');
      overlay.className = 'admin-nav-overlay';
      overlay.setAttribute('data-admin-nav-overlay', '');
      overlay.innerHTML = `
        <div class="admin-nav-overlay__card">
          <span class="admin-nav-overlay__spinner"></span>
          <span class="text-sm font-semibold tracking-wide">Opening page...</span>
        </div>
      `;
      document.body.appendChild(overlay);
      return overlay;
    };

    const prefetchPage = (href) => {
      if (!href || prefetched.has(href)) {
        return;
      }

      prefetched.add(href);
      fetch(href, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-Admin-Prefetch': '1',
        },
      }).catch(() => {
      });
    };

    links.forEach((link) => {
      const href = appendTabSession(link.getAttribute('href'));
      if (!href) {
        return;
      }

      link.setAttribute('href', href);

      link.addEventListener('mouseenter', () => prefetchPage(href), { passive: true });
      link.addEventListener('focus', () => prefetchPage(href), { passive: true });

      link.addEventListener('click', (event) => {
        if (
          event.defaultPrevented
          || event.metaKey
          || event.ctrlKey
          || event.shiftKey
          || event.altKey
          || link.target === '_blank'
        ) {
          return;
        }

        const latestUrl = new URL(window.location.href);
        const nextUrl = new URL(href, window.location.origin);
        if (latestUrl.pathname === nextUrl.pathname && latestUrl.search === nextUrl.search) {
          return;
        }

        ensureOverlay().classList.add('is-visible');
      });
    });

    window.addEventListener('pageshow', () => {
      if (overlay) {
        overlay.classList.remove('is-visible');
      }
    });

    window.addEventListener('storage', (event) => {
      if (event.key === adminNotificationUnreadKey) {
        localStorage.setItem(adminNotificationUnreadKey, '0');
      }
    });

    const isCompactViewport = () => window.matchMedia('(max-width: 1024px)').matches;

    const closeProfileMenu = () => {
      if (!profileMenu || !profileMenuToggle) {
        return;
      }

      profileMenu.classList.add('hidden');
      profileMenuToggle.setAttribute('aria-expanded', 'false');
    };

    const toggleProfileMenu = () => {
      if (!profileMenu || !profileMenuToggle) {
        return;
      }

      const isOpen = !profileMenu.classList.contains('hidden');
      profileMenu.classList.toggle('hidden', isOpen);
      profileMenuToggle.setAttribute('aria-expanded', String(!isOpen));
    };

    const closeSidebar = () => {
      if (!sidebar || !sidebarOverlay || !sidebarToggle) {
        return;
      }

      closeProfileMenu();
      sidebar.classList.remove('is-open');
      sidebarOverlay.classList.remove('is-visible');
      sidebarToggle.setAttribute('aria-expanded', 'false');
    };

    if (profileMenuToggle && profileMenu) {
      profileMenuToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        toggleProfileMenu();
      });

      profileMenu.addEventListener('click', (event) => {
        event.stopPropagation();
      });

      document.addEventListener('click', closeProfileMenu);

      if (sidebar) {
        sidebar.addEventListener('mouseleave', () => {
          if (!isCompactViewport()) {
            closeProfileMenu();
          }
        });
      }
    }

    const openSidebar = () => {
      if (!sidebar || !sidebarOverlay || !sidebarToggle) {
        return;
      }

      sidebar.classList.add('is-open');
      sidebarOverlay.classList.add('is-visible');
      sidebarToggle.setAttribute('aria-expanded', 'true');
    };

    if (sidebarToggle && sidebar && sidebarOverlay) {
      sidebarToggle.addEventListener('click', () => {
        if (!isCompactViewport()) {
          return;
        }

        const isOpen = sidebar.classList.contains('is-open');
        if (isOpen) {
          closeSidebar();
        } else {
          openSidebar();
        }
      });

      sidebarOverlay.addEventListener('click', closeSidebar);

      window.addEventListener('resize', () => {
        if (!isCompactViewport()) {
          closeSidebar();
        }
      });
    }

    links.forEach((link) => {
      link.addEventListener('click', () => {
        if (isCompactViewport()) {
          closeSidebar();
        }
      });
    });

  })();
</script>
