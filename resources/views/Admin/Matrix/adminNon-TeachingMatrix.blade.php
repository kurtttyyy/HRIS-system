<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Academic Non-Teaching Matrix</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    @media screen {
      html[data-theme="dark"] #admin-matrix-page .matrix-card-motion,
      html[data-theme="dark"] #admin-matrix-page .matrix-print-wrapper {
        background: #111c30 !important;
        border-color: #33445b !important;
        box-shadow: none !important;
      }
      html[data-theme="dark"] #admin-matrix-page h1,
      html[data-theme="dark"] #admin-matrix-page [class*="text-stone-9"],
      html[data-theme="dark"] #admin-matrix-page .matrix-name-button {
        color: #e5eaf2 !important;
      }
      html[data-theme="dark"] #admin-matrix-page [class*="text-stone-8"],
      html[data-theme="dark"] #admin-matrix-page [class*="text-stone-7"],
      html[data-theme="dark"] #admin-matrix-page [class*="text-stone-6"] {
        color: #b8c3d3 !important;
      }
      html[data-theme="dark"] #admin-matrix-page table thead {
        background: #18263d !important;
      }
      html[data-theme="dark"] #admin-matrix-page table th {
        color: #dbe3ee !important;
        border-color: #43536a !important;
      }
      html[data-theme="dark"] #admin-matrix-page table tbody,
      html[data-theme="dark"] #admin-matrix-page table tbody tr,
      html[data-theme="dark"] #admin-matrix-page table tbody tr:nth-child(odd),
      html[data-theme="dark"] #admin-matrix-page table tbody tr:nth-child(even) {
        background: #111c30 !important;
      }
      html[data-theme="dark"] #admin-matrix-page table td {
        color: #cbd5e1 !important;
        border-color: #34455c !important;
      }
      html[data-theme="dark"] #admin-matrix-page table tbody tr:hover {
        background: #17263c !important;
      }
    }

    .matrix-name-button {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      border: 0;
      background: transparent;
      padding: 0;
      color: #111827;
      font-weight: 600;
      text-align: left;
      cursor: pointer;
      text-decoration: none;
    }

    .matrix-name-button:hover {
      color: #111827;
      text-decoration: none;
    }

    .matrix-name-button:focus-visible {
      outline: 2px solid #93c5fd;
      outline-offset: 2px;
      border-radius: 0.25rem;
    }

    .matrix-row-active {
      background: #eff6ff !important;
    }

    .matrix-employee-modal {
      position: fixed;
      inset: 0;
      z-index: 90;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      background: rgba(15, 23, 42, 0.62);
    }

    .matrix-employee-modal.is-open {
      display: flex;
    }

    .matrix-employee-card {
      width: min(1200px, 100%);
      max-height: min(90vh, 860px);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid #d6d3d1;
      border-radius: 1rem;
      background: #ffffff;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.28);
    }

    .matrix-employee-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.25rem 1.5rem;
      background: #1c1917;
      color: #ffffff;
    }

    .matrix-employee-card-title {
      margin: 0;
      font-size: 1.7rem;
      font-weight: 700;
      letter-spacing: -0.03em;
    }

    .matrix-employee-close {
      border: 0;
      background: transparent;
      color: inherit;
      font-size: 1.6rem;
      cursor: pointer;
    }

    .matrix-employee-card-body {
      display: grid;
      grid-template-columns: 180px minmax(0, 1fr);
      gap: 1.25rem;
      padding: 1.5rem;
      overflow-y: auto;
    }

    .matrix-employee-avatar {
      display: flex;
      align-items: center;
      justify-content: center;
      align-self: start;
      width: 180px;
      aspect-ratio: 3 / 4;
      overflow: hidden;
      border: 1px solid #d6d3d1;
      background: linear-gradient(135deg, #f5f5f4, #e7e5e4);
      color: #111827;
      font-size: 3rem;
      font-weight: 700;
    }

    .matrix-employee-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center top;
    }

    .matrix-employee-fields {
      display: grid;
      gap: 1rem;
      align-content: start;
    }

    .matrix-employee-carousel {
      display: grid;
      gap: 1rem;
    }

    .matrix-employee-section {
      display: none;
      gap: 0.7rem;
    }

    .matrix-employee-section.is-active {
      display: grid;
    }

    .matrix-employee-section-title {
      margin: 0;
      padding-bottom: 0.35rem;
      border-bottom: 1px solid #e7e5e4;
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #57534e;
    }

    .matrix-employee-carousel-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding-top: 0.25rem;
      border-top: 1px solid #e7e5e4;
    }

    .matrix-employee-carousel-status {
      font-size: 0.84rem;
      font-weight: 600;
      color: #57534e;
    }

    .matrix-employee-carousel-actions {
      display: inline-flex;
      gap: 0.6rem;
    }

    .matrix-employee-carousel-button {
      border: 1px solid #a8a29e;
      border-radius: 9999px;
      background: #ffffff;
      padding: 0.5rem 1rem;
      color: #1c1917;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .matrix-employee-carousel-button:hover {
      background: #f5f5f4;
      border-color: #78716c;
    }

    .matrix-employee-carousel-button:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }

    .matrix-employee-field {
      display: grid;
      grid-template-columns: 130px minmax(0, 1fr);
      gap: 0.75rem;
      align-items: center;
    }

    .matrix-employee-label {
      font-size: 0.84rem;
      font-weight: 700;
      color: #292524;
    }

    .matrix-employee-value {
      min-height: 2.3rem;
      border: 1px solid #a8a29e;
      background: #fafaf9;
      padding: 0.55rem 0.7rem;
      color: #111827;
      font-size: 0.92rem;
    }

    .matrix-export-modal {
      position: fixed;
      inset: 0;
      z-index: 95;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      background: rgba(15, 23, 42, 0.55);
    }

    .matrix-export-modal.is-open {
      display: flex;
    }

    .matrix-export-card {
      width: min(420px, 100%);
      border: 1px solid #d6d3d1;
      border-radius: 1rem;
      background: #ffffff;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.24);
      overflow: hidden;
    }

    .matrix-export-card-header {
      padding: 1.1rem 1.4rem 0.4rem;
    }

    .matrix-export-card-body {
      padding: 0 1.4rem 1.4rem;
      display: grid;
      gap: 0.75rem;
    }

    @media (max-width: 640px) {
      .matrix-employee-card-body {
        grid-template-columns: 1fr;
      }

      .matrix-employee-avatar {
        width: min(220px, 100%);
        margin: 0 auto;
      }

      .matrix-employee-field {
        grid-template-columns: 1fr;
        gap: 0.35rem;
      }

      .matrix-employee-carousel-footer {
        flex-direction: column;
        align-items: stretch;
      }

      .matrix-employee-carousel-actions {
        justify-content: space-between;
      }
    }

    .matrix-main-content {
      width: calc(100vw - 4rem);
      min-width: 0;
      max-width: calc(100vw - 4rem);
    }

    .matrix-content-section,
    .matrix-print-wrapper {
      min-width: 0;
      max-width: 100%;
    }

    .matrix-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }

    .matrix-reveal.reveal-from-top {
      transform: translateY(-18px);
    }

    .matrix-reveal.is-visible {
      animation: matrix-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--matrix-delay, 0ms);
    }

    .matrix-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }

    .matrix-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }

    .matrix-data-row {
      transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    html[data-theme="dark"] main .matrix-data-row:hover {
      background-color: #1e293b !important;
      box-shadow: inset 4px 0 0 #38bdf8;
    }

    .matrix-icon-pop {
      animation: matrix-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--matrix-delay, 0ms);
    }

    @keyframes matrix-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes matrix-pop-in {
      0% {
        opacity: 0;
        transform: scale(0.82) rotate(-4deg);
      }
      100% {
        opacity: 1;
        transform: scale(1) rotate(0);
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .matrix-reveal,
      .matrix-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }

      .matrix-card-motion {
        transition: none;
      }

      .matrix-card-motion:hover {
        transform: none;
      }
    }

    @media (min-width: 1024px) {
      .matrix-admin-shell > .matrix-print-hide:hover + .matrix-main-content {
        margin-left: 18rem;
        width: calc(100vw - 18rem);
        max-width: calc(100vw - 18rem);
      }
    }

    @media (max-width: 1023px) {
      .matrix-main-content {
        width: 100%;
        max-width: 100%;
      }
    }

    @media print {
      @page {
        size: 13in 8.5in;
        margin: 0.35in;
      }

      html,
      body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      body * {
        visibility: hidden;
      }

      .matrix-print-section,
      .matrix-print-section * {
        visibility: visible;
      }

      .matrix-print-section {
        position: static !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
      }

      .matrix-print-shell,
      .matrix-print-shell > main {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }

      .matrix-print-wrapper {
        overflow: visible !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #ffffff !important;
        opacity: 1 !important;
        transform: none !important;
        animation: none !important;
      }

      .matrix-print-wrapper table {
        min-width: 100% !important;
        width: auto !important;
        table-layout: auto !important;
      }

      .matrix-print-wrapper th,
      .matrix-print-wrapper td {
        font-size: 8.75pt !important;
        padding: 4pt 4pt !important;
        word-break: normal !important;
        overflow-wrap: break-word !important;
        white-space: normal !important;
      }

      .matrix-print-wrapper th:first-child,
      .matrix-print-wrapper td:first-child {
        width: 1.9in !important;
        min-width: 1.9in !important;
        white-space: nowrap !important;
      }

      .matrix-print-wrapper th:nth-child(2),
      .matrix-print-wrapper td:nth-child(2) {
        width: 2.6in !important;
        min-width: 2.6in !important;
      }

      .matrix-print-hide {
        display: none !important;
      }

      .matrix-export-actions {
        display: none !important;
      }
    }
  </style>
</head>
<body class="bg-[#f4f7fd]">

<div class="matrix-admin-shell matrix-print-shell flex min-h-screen">
  <div class="matrix-print-hide">
    @include('components.adminSideBar')
  </div>

  <main id="admin-matrix-page" class="matrix-main-content flex-1 ml-16 transition-all duration-300">
    <section class="matrix-content-section matrix-print-hide px-4 md:px-8 pt-8 pb-6">
      <div class="matrix-card-motion matrix-reveal rounded-2xl border border-stone-300 bg-white/80 backdrop-blur-sm shadow-sm p-5 md:p-7" style="--matrix-delay: 0ms;">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-600">Matrix 15</p>
            <h1 class="mt-1 text-xl md:text-2xl font-semibold text-stone-900">
              Matrix List of Academic Non-Teaching Personnel
            </h1>
            <p class="mt-1 text-sm text-stone-600">
                Matrix list of Academic Non-Teaching personnel including (Registrar, Librarian, Guidance Counselor, Researcher, etc.)
            </p>
          </div>
          <div class="matrix-export-actions matrix-reveal flex flex-wrap items-center gap-2" style="--matrix-delay: 80ms;">
            <button
              type="button"
              onclick="downloadMatrixWord('non-teaching-matrix', 'Academic Non-Teaching Matrix')"
              class="matrix-card-motion inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100"
            >
              <i class="matrix-icon-pop fa-solid fa-file-word" style="--matrix-delay: 110ms;"></i>
              Word
            </button>
            <button
              type="button"
              onclick="openMatrixExcelExportModal('non-teaching-matrix', 'Academic Non-Teaching Matrix')"
              class="matrix-card-motion inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100"
            >
              <i class="matrix-icon-pop fa-solid fa-file-excel" style="--matrix-delay: 140ms;"></i>
              Excel
            </button>
            <button
              type="button"
              onclick="window.print()"
              class="matrix-card-motion inline-flex items-center gap-2 rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100"
            >
              <i class="matrix-icon-pop fa-solid fa-file-pdf" style="--matrix-delay: 170ms;"></i>
              PDF
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="matrix-content-section matrix-print-section px-4 md:px-8 pb-10">
      @php
        $rows = collect($nonTeachingEmployees ?? ($administrators ?? []))
          ->filter(function ($staff) {
            $normalize = function ($value) {
              return strtolower(trim((string) ($value ?? '')));
            };
            $jobType = $normalize(optional($staff->employee)->job_type ?: optional(optional($staff->applicant)->position)->job_type);
            if (!in_array($jobType, ['non-teaching', 'non teaching', 'nt'], true)) {
              return false;
            }

            $hasJobRole = trim((string) ($staff->job_role ?? '')) !== '';
            $hasDepartmentHead = trim((string) ($staff->department_head ?? '')) !== '';
            return !($hasJobRole && $hasDepartmentHead);
          })
          ->values();
      @endphp
      <div class="matrix-print-wrapper w-full overflow-x-auto rounded-2xl border border-stone-300 bg-white shadow-sm">
        <table id="non-teaching-matrix" class="min-w-[1320px] w-full text-sm text-stone-800 border-collapse">
          <thead class="bg-stone-100">
            <tr class="hidden print:table-row">
              <th colspan="7" class="border border-stone-900 bg-white px-3 py-2 text-left text-sm font-bold text-stone-900">
                15. Matrix list of academic non-teaching personnel including (Registrar, Librarian, Guidance Counselor, Researcher, etc.)
              </th>
            </tr>
            <tr>
              <th class="border border-stone-300 px-3 py-3 text-left font-semibold w-[20%]">Names of Non-Teaching</th>
              <th class="border border-stone-300 px-3 py-3 text-left font-semibold w-[340px]">Educational Qualifications</th>
              <th class="border border-stone-300 px-3 py-3 text-left font-semibold w-[220px]">Feild/s of Specialization</th>
              <th class="border border-stone-300 px-3 py-3 text-left font-semibold w-[150px]">Status of Employement</th>
              <th class="border border-stone-300 px-3 py-3 text-left font-semibold w-[190px]">Rate of salary per hour/month</th>
              <th class="border border-stone-300 px-3 py-3 text-left font-semibold w-[220px]">Other Employement benefits</th>
              <th class="border border-stone-300 px-3 py-3 text-left font-semibold w-[320px]">Relevant Experience/s</th>
            </tr>
          </thead>
          <tbody class="align-top">
            @forelse ($rows as $staff)
              @php
                $fullName = trim(implode(' ', array_filter([
                  trim((string) ($staff->first_name ?? '')),
                  trim((string) ($staff->middle_name ?? '')),
                  trim((string) ($staff->last_name ?? '')),
                ])));

                $hasDegreeValue = function ($value): bool {
                  return !in_array(strtolower(trim((string) ($value ?? ''))), ['', '-', 'n/a', 'na'], true);
                };
                $degreeRows = collect(optional($staff->applicant)->degrees ?? [])
                  ->filter(fn ($row) => $hasDegreeValue($row->degree_name ?? null))
                  ->values();
                $fallbackDegrees = collect([
                  ['degree_name' => trim((string) optional($staff->education)->doctorate), 'school_name' => trim((string) (optional($staff->applicant)->doctoral_school_name ?? '')), 'year_finished' => trim((string) (optional($staff->applicant)->doctoral_year_finished ?? ''))],
                  ['degree_name' => trim((string) optional($staff->education)->master), 'school_name' => trim((string) (optional($staff->applicant)->master_school_name ?? '')), 'year_finished' => trim((string) (optional($staff->applicant)->master_year_finished ?? ''))],
                  ['degree_name' => trim((string) optional($staff->education)->bachelor), 'school_name' => trim((string) (optional($staff->applicant)->bachelor_school_name ?? '')), 'year_finished' => trim((string) (optional($staff->applicant)->bachelor_year_finished ?? ''))],
                ])->filter(fn ($row) => $hasDegreeValue($row['degree_name']));

                $specialization = collect([
                  trim((string) (optional($staff->applicant)->field_study ?? '')),
                  trim((string) (optional($staff->applicant)->skills_n_expertise ?? '')),
                  trim((string) (optional(optional($staff->applicant)->position)->skills ?? '')),
                ])->filter()->unique()->values();

                $jobType = strtolower(trim((string) (optional($staff->employee)->job_type ?? optional(optional($staff->applicant)->position)->job_type ?? '')));
                $rawJoinDate = optional($staff->applicant)->date_hired ?? optional($staff->employee)->employement_date;
                $employmentStatus = 'N/A';
                if ($jobType === 'non-teaching' || $jobType === 'teaching') {
                  $employmentStatus = 'Probationary';
                  if (!empty($rawJoinDate)) {
                    try {
                      $joinDate = \Carbon\Carbon::parse($rawJoinDate);
                      $threshold = $jobType === 'non-teaching'
                        ? $joinDate->copy()->addMonths(6)
                        : $joinDate->copy()->addYears(3);
                      $employmentStatus = now()->lt($threshold) ? 'Probationary' : 'Permanent';
                    } catch (\Throwable $e) {
                      $employmentStatus = 'Probationary';
                    }
                  }
                }
                $regularizationDateDisplay = '';
                if (!empty($rawJoinDate)) {
                  try {
                    $joinDate = \Carbon\Carbon::parse($rawJoinDate);
                    $regularizationDateDisplay = ($jobType === 'non-teaching'
                      ? $joinDate->copy()->addMonths(6)
                      : $joinDate->copy()->addYears(3))->format('m/d/Y');
                  } catch (\Throwable $e) {
                    $regularizationDateDisplay = '';
                  }
                }

                $isPlaceholder = function ($value): bool {
                  $normalized = strtolower(trim((string) ($value ?? '')));
                  return $normalized === '' || $normalized === 'n/a' || $normalized === 'na' || $normalized === '-';
                };
                $normalizeSalary = function ($value) use ($isPlaceholder): string {
                  $value = trim((string) ($value ?? ''));
                  if ($isPlaceholder($value)) {
                    return '';
                  }

                  $numericValue = preg_replace('/[^0-9.\-]/', '', $value);
                  if ($numericValue !== '' && is_numeric($numericValue) && (float) $numericValue === 0.0) {
                    return '';
                  }
                  if ($numericValue !== '' && is_numeric($numericValue)) {
                    $decimalPart = str_contains($numericValue, '.')
                      ? rtrim(substr($numericValue, strpos($numericValue, '.') + 1), '0')
                      : '';
                    return number_format((float) $numericValue, strlen($decimalPart), '.', ',');
                  }

                  return $value;
                };
                $salaryPerMonth = $normalizeSalary(optional($staff->salary)->salary);
                $salaryPerHour = $normalizeSalary(optional($staff->salary)->rate_per_hour);
                $allowance = $normalizeSalary(optional($staff->salary)->cola);
                $salaryText = '-';
                if ($salaryPerHour !== '' && $salaryPerMonth !== '') {
                  $salaryText = 'Hr: '.$salaryPerHour.' / Mo: '.$salaryPerMonth;
                } elseif ($salaryPerHour !== '') {
                  $salaryText = $salaryPerHour.' per hour';
                } elseif ($salaryPerMonth !== '') {
                  $salaryText = $salaryPerMonth.' per month';
                }

                $benefits = collect([
                  $allowance !== '' ? 'Allowance: '.$allowance : '',
                  trim((string) (optional($staff->applicant)->benefit ?? '')),
                  trim((string) (optional(optional($staff->applicant)->position)->benifits ?? '')),
                ])->filter()->values();

                $workPositionRaw = trim((string) (optional($staff->applicant)->work_position ?? ''));
                $workDurationRaw = trim((string) (optional($staff->applicant)->work_duration ?? ''));
                $workPosition = $isPlaceholder($workPositionRaw) ? '' : $workPositionRaw;
                $workDuration = $isPlaceholder($workDurationRaw) ? '' : $workDurationRaw;
                if ($workPosition === '') {
                  $positionFallback = collect([
                    trim((string) (optional($staff->employee)->position ?? '')),
                    trim((string) (optional(optional($staff->applicant)->position)->title ?? '')),
                    trim((string) ($staff->position ?? '')),
                  ])->first(fn ($value) => !$isPlaceholder($value));
                  $workPosition = $positionFallback ?? '';
                }
                if ($workDuration === '' && !empty($rawJoinDate)) {
                  try {
                    $joinYear = (int) \Carbon\Carbon::parse($rawJoinDate)->format('Y');
                    $workDuration = $joinYear > 0 ? ($joinYear.'-'.now()->format('Y')) : '';
                  } catch (\Throwable $e) {
                    $workDuration = '';
                  }
                }
                $currentYear = (int) now()->format('Y');
                $displayWorkDuration = $workDuration !== ''
                  ? preg_replace_callback('/(\b\d{4}\b)(\s*[-–]\s*)(\d{4})/', function ($matches) use ($currentYear) {
                      $endYear = (int) ($matches[3] ?? 0);
                      if ($endYear === $currentYear) {
                          return ($matches[1] ?? '').($matches[2] ?? '-').'Present';
                      }
                      return $matches[0] ?? '';
                    }, $workDuration)
                  : '';
                $experienceLines = collect(preg_split('/\s*(?:\||;|\/|\r?\n)\s*/', $workPosition) ?: [])
                  ->map(fn ($value) => trim((string) preg_replace('/\s*\(?\d{4}\s*[-–]\s*(?:\d{4}|Present)\)?\s*$/i', '', (string) $value)))
                  ->filter(fn ($value) => $value !== '' && !$isPlaceholder($value))
                  ->reverse()
                  ->values();
                if ($displayWorkDuration === '' && $experienceLines->count() > 1) {
                  $fallbackYear = $currentYear;
                  if (!empty($rawJoinDate)) {
                    try {
                      $fallbackYear = (int) \Carbon\Carbon::parse($rawJoinDate)->format('Y');
                    } catch (\Throwable $e) {
                      $fallbackYear = $currentYear;
                    }
                  }
                  $displayWorkDuration = ($fallbackYear > 0 ? $fallbackYear : $currentYear).'-Present';
                }
                $employeeIdDisplay = trim((string) (optional($staff->employee)->employee_id ?? $staff->employee_id ?? $staff->id ?? ''));
                $employeeIdDisplay = $employeeIdDisplay !== '' ? $employeeIdDisplay : 'N/A';
                $ageDisplay = 'N/A';
                if (!empty(optional($staff->employee)->birthday)) {
                  try {
                    $ageDisplay = (string) \Carbon\Carbon::parse(optional($staff->employee)->birthday)->age;
                  } catch (\Throwable $e) {
                    $ageDisplay = 'N/A';
                  }
                }
                $genderDisplay = collect([
                  trim((string) ($staff->gender ?? '')),
                  trim((string) (optional($staff->employee)->sex ?? '')),
                  trim((string) (optional($staff->applicant)->gender ?? '')),
                ])->first(fn ($value) => $value !== '') ?? 'N/A';
                $positionDisplay = collect([
                  trim((string) ($staff->job_role ?? '')),
                  trim((string) (optional($staff->employee)->position ?? '')),
                  trim((string) (optional(optional($staff->applicant)->position)->title ?? '')),
                  trim((string) ($staff->position ?? '')),
                ])->first(fn ($value) => $value !== '') ?? 'N/A';
                $departmentDisplay = collect([
                  trim((string) (optional($staff->employee)->department ?? '')),
                  trim((string) (optional(optional($staff->applicant)->position)->department ?? '')),
                  trim((string) ($staff->department ?? '')),
                ])->first(fn ($value) => $value !== '') ?? 'N/A';
                $hireDateDisplay = 'N/A';
                if (!empty($rawJoinDate)) {
                  try {
                    $hireDateDisplay = \Carbon\Carbon::parse($rawJoinDate)->format('m/d/Y');
                  } catch (\Throwable $e) {
                    $hireDateDisplay = trim((string) $rawJoinDate) !== '' ? trim((string) $rawJoinDate) : 'N/A';
                  }
                }
                $initials = collect([
                  mb_substr(trim((string) ($staff->first_name ?? '')), 0, 1),
                  mb_substr(trim((string) ($staff->last_name ?? '')), 0, 1),
                ])->filter()->implode('');
                $initials = $initials !== '' ? strtoupper($initials) : 'NA';
                $profilePhotoDocument = optional($staff->applicant)->documents
                  ?->first(function ($doc) {
                    return strtoupper(trim((string) ($doc->type ?? ''))) === 'PROFILE_PHOTO' && !empty($doc->filepath);
                  });
                if (!$profilePhotoDocument) {
                  $profilePhotoDocument = optional($staff->applicant)->documents
                    ?->first(function ($doc) {
                      $mime = strtolower(trim((string) ($doc->mime_type ?? '')));
                      $filename = strtolower(trim((string) ($doc->filename ?? '')));
                      return !empty($doc->filepath) && (str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|gif|webp)$/i', $filename));
                    });
                }
                $profilePhotoUrl = $profilePhotoDocument?->filepath ? asset('storage/'.$profilePhotoDocument->filepath) : null;
                $middleNameDisplay = trim((string) ($staff->middle_name ?? '')) !== '' ? trim((string) ($staff->middle_name ?? '')) : 'N/A';
                $accountNumberDisplay = trim((string) (optional($staff->employee)->account_number ?? '')) !== '' ? trim((string) (optional($staff->employee)->account_number ?? '')) : 'N/A';
                $civilStatusDisplay = trim((string) (optional($staff->employee)->civil_status ?? '')) !== '' ? trim((string) (optional($staff->employee)->civil_status ?? '')) : 'N/A';
                $contactNumberDisplay = collect([
                  trim((string) (optional($staff->employee)->contact_number ?? '')),
                  trim((string) (optional($staff->applicant)->phone ?? '')),
                ])->first(fn ($value) => $value !== '') ?? 'N/A';
                $birthdayDisplay = 'N/A';
                if (!empty(optional($staff->employee)->birthday)) {
                  try {
                    $birthdayDisplay = \Carbon\Carbon::parse(optional($staff->employee)->birthday)->format('F j, Y');
                  } catch (\Throwable $e) {
                    $birthdayDisplay = trim((string) (optional($staff->employee)->birthday ?? '')) !== '' ? trim((string) (optional($staff->employee)->birthday ?? '')) : 'N/A';
                  }
                }
                $addressDisplay = collect([
                  trim((string) (optional($staff->employee)->address ?? '')),
                  trim((string) (optional($staff->applicant)->address ?? '')),
                ])->first(fn ($value) => $value !== '') ?? 'N/A';
                $classificationDisplay = collect([
                  trim((string) (optional($staff->employee)->classification ?? '')),
                  trim((string) (optional(optional($staff->applicant)->position)->employment ?? '')),
                  trim((string) (optional($staff->employee)->job_type ?? '')),
                ])->first(fn ($value) => $value !== '') ?? 'N/A';
                $licenseDisplay = trim((string) (optional($staff->license)->license ?? '')) !== '' ? trim((string) (optional($staff->license)->license ?? '')) : 'N/A';
                $registrationNumberDisplay = trim((string) (optional($staff->license)->registration_number ?? '')) !== '' ? trim((string) (optional($staff->license)->registration_number ?? '')) : 'N/A';
                $registrationDateDisplay = 'N/A';
                if (!empty(optional($staff->license)->registration_date)) {
                  try {
                    $registrationDateDisplay = \Carbon\Carbon::parse(optional($staff->license)->registration_date)->format('F j, Y');
                  } catch (\Throwable $e) {
                    $registrationDateDisplay = trim((string) (optional($staff->license)->registration_date ?? '')) !== '' ? trim((string) (optional($staff->license)->registration_date ?? '')) : 'N/A';
                  }
                }
                $validUntilDisplay = 'N/A';
                if (!empty(optional($staff->license)->valid_until)) {
                  try {
                    $validUntilDisplay = \Carbon\Carbon::parse(optional($staff->license)->valid_until)->format('F j, Y');
                  } catch (\Throwable $e) {
                    $validUntilDisplay = trim((string) (optional($staff->license)->valid_until ?? '')) !== '' ? trim((string) (optional($staff->license)->valid_until ?? '')) : 'N/A';
                  }
                }
                $bachelorDisplay = trim((string) (optional($staff->education)->bachelor ?? '')) !== '' ? trim((string) (optional($staff->education)->bachelor ?? '')) : 'N/A';
                $masterDisplay = trim((string) (optional($staff->education)->master ?? '')) !== '' ? trim((string) (optional($staff->education)->master ?? '')) : 'N/A';
                $doctorateDisplay = trim((string) (optional($staff->education)->doctorate ?? '')) !== '' ? trim((string) (optional($staff->education)->doctorate ?? '')) : 'N/A';
                $salaryDisplay = trim((string) (optional($staff->salary)->salary ?? '')) !== '' ? trim((string) (optional($staff->salary)->salary ?? '')) : 'N/A';
                $colaDisplay = trim((string) (optional($staff->salary)->cola ?? '')) !== '' ? trim((string) (optional($staff->salary)->cola ?? '')) : 'N/A';
                $sssDisplay = trim((string) (optional($staff->government)->SSS ?? '')) !== '' ? trim((string) (optional($staff->government)->SSS ?? '')) : 'N/A';
                $tinDisplay = trim((string) (optional($staff->government)->TIN ?? '')) !== '' ? trim((string) (optional($staff->government)->TIN ?? '')) : 'N/A';
                $philHealthDisplay = trim((string) (optional($staff->government)->PhilHealth ?? '')) !== '' ? trim((string) (optional($staff->government)->PhilHealth ?? '')) : 'N/A';
                $pagIbigMidDisplay = trim((string) (optional($staff->government)->MID ?? '')) !== '' ? trim((string) (optional($staff->government)->MID ?? '')) : 'N/A';
                $pagIbigRtnDisplay = trim((string) (optional($staff->government)->RTN ?? '')) !== '' ? trim((string) (optional($staff->government)->RTN ?? '')) : 'N/A';
                $matrixEmployeeModalData = [
                  'employee_id' => $employeeIdDisplay,
                  'first_name' => trim((string) ($staff->first_name ?? '')) !== '' ? trim((string) ($staff->first_name ?? '')) : 'N/A',
                  'middle_name' => $middleNameDisplay,
                  'last_name' => trim((string) ($staff->last_name ?? '')) !== '' ? trim((string) ($staff->last_name ?? '')) : 'N/A',
                  'gender' => $genderDisplay,
                  'birthday' => $birthdayDisplay,
                  'account_number' => $accountNumberDisplay,
                  'civil_status' => $civilStatusDisplay,
                  'contact_number' => $contactNumberDisplay,
                  'address' => $addressDisplay,
                  'hired_date' => $hireDateDisplay,
                  'position' => $positionDisplay,
                  'department' => $departmentDisplay,
                  'full_name' => $fullName !== '' ? $fullName : 'N/A',
                  'age' => $ageDisplay,
                  'rate' => $salaryText !== '' ? $salaryText : 'N/A',
                  'salary' => $salaryDisplay,
                  'rate_per_hour' => $salaryPerHour !== '' ? $salaryPerHour : 'N/A',
                  'cola' => $colaDisplay,
                  'classification' => $classificationDisplay,
                  'employment_status' => $employmentStatus,
                  'regularization_date' => $regularizationDateDisplay,
                  'benefits_text' => $benefits->implode(' | '),
                  'bachelor_degree' => $bachelorDisplay,
                  'master_degree' => $masterDisplay,
                  'doctorate_degree' => $doctorateDisplay,
                  'license' => $licenseDisplay,
                  'registration_number' => $registrationNumberDisplay,
                  'registration_date' => $registrationDateDisplay,
                  'valid_until' => $validUntilDisplay,
                  'sss' => $sssDisplay,
                  'tin' => $tinDisplay,
                  'philhealth' => $philHealthDisplay,
                  'pagibig_mid' => $pagIbigMidDisplay,
                  'pagibig_rtn' => $pagIbigRtnDisplay,
                  'initials' => $initials,
                  'profile_photo_url' => $profilePhotoUrl,
                ];
              @endphp
              <tr class="matrix-data-row odd:bg-white even:bg-stone-50/40">
                <td class="border border-stone-300 px-3 py-3 font-medium">
                  <span
                    role="button"
                    tabindex="0"
                    class="matrix-name-button"
                    data-matrix-employee="{{ e(json_encode($matrixEmployeeModalData)) }}"
                    data-matrix-employee-full-name="{{ e($fullName !== '' ? $fullName : 'N/A') }}"
                    data-matrix-employee-gender="{{ e($genderDisplay) }}"
                    data-matrix-employee-age="{{ e($ageDisplay) }}"
                    data-matrix-employee-birthday="{{ e(trim((string) (optional($staff->employee)->birthday ?? ''))) }}"
                    data-matrix-employee-rate="{{ e($salaryPerHour !== '' ? $salaryPerHour : 'N/A') }}"
                    data-matrix-employee-hired-date="{{ e($hireDateDisplay) }}"
                    data-matrix-employee-regularization-date="{{ e($regularizationDateDisplay) }}"
                    data-matrix-employee-position="{{ e($positionDisplay) }}"
                    data-matrix-employee-employment-status="{{ e($employmentStatus) }}"
                    onclick="openMatrixEmployeeModal({{ \Illuminate\Support\Js::from($matrixEmployeeModalData) }})"
                    onkeydown="handleMatrixNameKeydown(event, this)"
                  >
                    {{ $fullName !== '' ? $fullName : '-' }}
                  </span>
                </td>
                <td class="border border-stone-300 px-3 py-3">
                  @if ($degreeRows->isNotEmpty())
                    <div class="space-y-1">
                      @foreach ($degreeRows as $row)
                        <div>
                          - <strong>{{ trim((string) ($row->degree_name ?? '-')) }}</strong>
                          @if (trim((string) ($row->school_name ?? '')) !== '')
                            , <span class="italic">{{ trim((string) $row->school_name) }}</span>
                          @endif
                          @if (trim((string) ($row->year_finished ?? '')) !== '')
                            , {{ trim((string) $row->year_finished) }}
                          @endif
                        </div>
                      @endforeach
                    </div>
                  @elseif ($fallbackDegrees->isNotEmpty())
                    <div class="space-y-1">
                      @foreach ($fallbackDegrees as $row)
                        <div>
                          - <strong>{{ $row['degree_name'] }}</strong>
                          @if ($row['school_name'] !== '')
                            , <span class="italic">{{ $row['school_name'] }}</span>
                          @endif
                          @if ($row['year_finished'] !== '')
                            , {{ $row['year_finished'] }}
                          @endif
                        </div>
                      @endforeach
                    </div>
                  @else
                    -
                  @endif
                </td>
                <td class="border border-stone-300 px-3 py-3">
                  @if ($specialization->isNotEmpty())
                    {{ $specialization->implode(', ') }}
                  @else
                    -
                  @endif
                </td>
                <td class="border border-stone-300 px-3 py-3">{{ $employmentStatus !== '' ? $employmentStatus : '-' }}</td>
                <td class="border border-stone-300 px-3 py-3">{{ $salaryText }}</td>
                <td class="border border-stone-300 px-3 py-3">
                  @if ($benefits->isNotEmpty())
                    {{ $benefits->implode(', ') }}
                  @else
                    -
                  @endif
                </td>
                <td class="border border-stone-300 px-3 py-3">
                  @if ($experienceLines->isNotEmpty() || $displayWorkDuration !== '')
                    <div class="space-y-1">
                      @foreach ($experienceLines as $line)
                        <div>&bull; {{ $line }}</div>
                        @if ($displayWorkDuration !== '')
                          <div class="pl-4 text-stone-700">({{ $displayWorkDuration }})</div>
                        @endif
                      @endforeach
                    </div>
                  @else
                    -
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="border border-stone-300 px-3 py-6 text-center text-stone-500">
                  No non-teaching employees found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<div id="matrix-employee-modal" class="matrix-employee-modal" onclick="closeMatrixEmployeeModalOnBackdrop(event)">
  <div class="matrix-employee-card" role="dialog" aria-modal="true" aria-labelledby="matrix-employee-modal-title">
    <div class="matrix-employee-card-header">
      <h3 id="matrix-employee-modal-title" class="matrix-employee-card-title">Employee Information</h3>
      <button type="button" class="matrix-employee-close" onclick="closeMatrixEmployeeModal()" aria-label="Close modal">&times;</button>
    </div>
    <div class="matrix-employee-card-body">
      <div id="matrix-employee-avatar" class="matrix-employee-avatar">NA</div>
      <div class="matrix-employee-fields">
        <div class="matrix-employee-carousel">
          <div class="matrix-employee-section is-active" data-matrix-employee-section>
            <p class="matrix-employee-section-title">Personal Information</p>
            <div class="matrix-employee-field"><div class="matrix-employee-label">ID Number:</div><div id="matrix-employee-id" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Last Name:</div><div id="matrix-employee-last-name" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">First Name:</div><div id="matrix-employee-first-name" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Middle Name:</div><div id="matrix-employee-middle-name" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Account No.:</div><div id="matrix-employee-account-number" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Sex:</div><div id="matrix-employee-gender" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Civil Status:</div><div id="matrix-employee-civil-status" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Contact No.:</div><div id="matrix-employee-contact-number" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Date of Birth:</div><div id="matrix-employee-birthday" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Address:</div><div id="matrix-employee-address" class="matrix-employee-value">-</div></div>
          </div>
          <div class="matrix-employee-section" data-matrix-employee-section>
            <p class="matrix-employee-section-title">Employment And License</p>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Employment Date:</div><div id="matrix-employee-hired-date" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Position:</div><div id="matrix-employee-position" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Department:</div><div id="matrix-employee-department" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Classification:</div><div id="matrix-employee-classification" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">License:</div><div id="matrix-employee-license" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Registration No.:</div><div id="matrix-employee-registration-number" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Registration Date:</div><div id="matrix-employee-registration-date" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Valid Until:</div><div id="matrix-employee-valid-until" class="matrix-employee-value">-</div></div>
          </div>
          <div class="matrix-employee-section" data-matrix-employee-section>
            <p class="matrix-employee-section-title">Education And Salary</p>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Bachelor's:</div><div id="matrix-employee-bachelor-degree" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Master's:</div><div id="matrix-employee-master-degree" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Doctorate:</div><div id="matrix-employee-doctorate-degree" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Basic Salary:</div><div id="matrix-employee-salary" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Rate per Hour:</div><div id="matrix-employee-rate-per-hour" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">COLA:</div><div id="matrix-employee-cola" class="matrix-employee-value">-</div></div>
          </div>
          <div class="matrix-employee-section" data-matrix-employee-section>
            <p class="matrix-employee-section-title">Government IDs</p>
            <div class="matrix-employee-field"><div class="matrix-employee-label">SSS:</div><div id="matrix-employee-sss" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">TIN:</div><div id="matrix-employee-tin" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">PhilHealth:</div><div id="matrix-employee-philhealth" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Pag-IBIG MID:</div><div id="matrix-employee-pagibig-mid" class="matrix-employee-value">-</div></div>
            <div class="matrix-employee-field"><div class="matrix-employee-label">Pag-IBIG RTN:</div><div id="matrix-employee-pagibig-rtn" class="matrix-employee-value">-</div></div>
          </div>
          <div class="matrix-employee-carousel-footer">
            <div id="matrix-employee-section-status" class="matrix-employee-carousel-status">Section 1 of 4</div>
            <div class="matrix-employee-carousel-actions">
              <button type="button" id="matrix-employee-prev-section" class="matrix-employee-carousel-button" onclick="changeMatrixEmployeeSection(-1)">Previous</button>
              <button type="button" id="matrix-employee-next-section" class="matrix-employee-carousel-button" onclick="changeMatrixEmployeeSection(1)">Next</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="matrix-excel-export-modal" class="matrix-export-modal" onclick="closeMatrixExcelExportModalOnBackdrop(event)">
  <div class="matrix-export-card" role="dialog" aria-modal="true" aria-labelledby="matrix-excel-export-title">
    <div class="matrix-export-card-header">
      <h3 id="matrix-excel-export-title" class="text-lg font-semibold text-stone-900">Choose Excel Format</h3>
      <p class="mt-1 text-sm text-stone-600">Select which version you want to export.</p>
    </div>
    <div class="matrix-export-card-body">
      <button type="button" onclick="confirmMatrixExcelExport('CHED')" class="inline-flex items-center justify-center rounded-lg border border-blue-300 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">CHED</button>
      <button type="button" onclick="confirmMatrixExcelExport('DOLE')" class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">DOLE</button>
      <button type="button" onclick="closeMatrixExcelExportModal()" class="inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-4 py-2.5 text-sm font-medium text-stone-700 hover:bg-stone-100">Cancel</button>
    </div>
  </div>
</div>

<script>
  (function () {
    const initMatrixPageAnimation = () => {
      const page = document.getElementById('admin-matrix-page');
      if (!page) return;

      const revealItems = Array.from(page.querySelectorAll('.matrix-reveal'));
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
      document.addEventListener('DOMContentLoaded', initMatrixPageAnimation, { once: true });
    } else {
      initMatrixPageAnimation();
    }
  })();

  let matrixExcelExportState = { tableId: null, title: null };
  let matrixEmployeeSectionIndex = 0;

  function renderMatrixEmployeeSection() {
    const modal = document.getElementById('matrix-employee-modal');
    const sections = modal ? Array.from(modal.querySelectorAll('[data-matrix-employee-section]')) : [];
    if (!sections.length) {
      return;
    }

    matrixEmployeeSectionIndex = Math.min(Math.max(matrixEmployeeSectionIndex, 0), sections.length - 1);

    sections.forEach((section, index) => {
      section.classList.toggle('is-active', index === matrixEmployeeSectionIndex);
    });

    const status = document.getElementById('matrix-employee-section-status');
    if (status) {
      status.textContent = `Section ${matrixEmployeeSectionIndex + 1} of ${sections.length}`;
    }

    const prevButton = document.getElementById('matrix-employee-prev-section');
    if (prevButton) {
      prevButton.disabled = matrixEmployeeSectionIndex === 0;
    }

    const nextButton = document.getElementById('matrix-employee-next-section');
    if (nextButton) {
      nextButton.disabled = matrixEmployeeSectionIndex === sections.length - 1;
    }
  }

  function changeMatrixEmployeeSection(direction) {
    matrixEmployeeSectionIndex += Number(direction) || 0;
    renderMatrixEmployeeSection();
  }

  function openMatrixEmployeeModal(employee) {
    const modal = document.getElementById('matrix-employee-modal');
    if (!modal || !employee) {
      return;
    }

    const map = {
      'matrix-employee-id': employee.employee_id,
      'matrix-employee-first-name': employee.first_name,
      'matrix-employee-middle-name': employee.middle_name,
      'matrix-employee-last-name': employee.last_name,
      'matrix-employee-account-number': employee.account_number,
      'matrix-employee-gender': employee.gender,
      'matrix-employee-civil-status': employee.civil_status,
      'matrix-employee-contact-number': employee.contact_number,
      'matrix-employee-birthday': employee.birthday,
      'matrix-employee-address': employee.address,
      'matrix-employee-hired-date': employee.hired_date,
      'matrix-employee-position': employee.position,
      'matrix-employee-department': employee.department,
      'matrix-employee-classification': employee.classification,
      'matrix-employee-license': employee.license,
      'matrix-employee-registration-number': employee.registration_number,
      'matrix-employee-registration-date': employee.registration_date,
      'matrix-employee-valid-until': employee.valid_until,
      'matrix-employee-bachelor-degree': employee.bachelor_degree,
      'matrix-employee-master-degree': employee.master_degree,
      'matrix-employee-doctorate-degree': employee.doctorate_degree,
      'matrix-employee-salary': employee.salary,
      'matrix-employee-rate-per-hour': employee.rate_per_hour,
      'matrix-employee-cola': employee.cola,
      'matrix-employee-sss': employee.sss,
      'matrix-employee-tin': employee.tin,
      'matrix-employee-philhealth': employee.philhealth,
      'matrix-employee-pagibig-mid': employee.pagibig_mid,
      'matrix-employee-pagibig-rtn': employee.pagibig_rtn,
    };

    Object.entries(map).forEach(([id, value]) => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = value || 'N/A';
      }
    });

    const avatar = document.getElementById('matrix-employee-avatar');
    if (avatar) {
      if (employee.profile_photo_url) {
        avatar.innerHTML = `<img src="${employee.profile_photo_url}" alt="Employee Photo">`;
      } else {
        avatar.textContent = employee.initials || 'NA';
      }
    }

    matrixEmployeeSectionIndex = 0;
    renderMatrixEmployeeSection();
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function handleMatrixNameKeydown(event, trigger) {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }

    event.preventDefault();
    trigger.click();
  }

  function closeMatrixEmployeeModal() {
    const modal = document.getElementById('matrix-employee-modal');
    if (!modal) {
      return;
    }

    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeMatrixEmployeeModalOnBackdrop(event) {
    if (event.target?.id !== 'matrix-employee-modal') {
      return;
    }

    closeMatrixEmployeeModal();
  }

  function openMatrixExcelExportModal(tableId, title) {
    const modal = document.getElementById('matrix-excel-export-modal');
    if (!modal) {
      downloadMatrixExcel(tableId, title, 'CHED');
      return;
    }

    matrixExcelExportState = { tableId, title };
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeMatrixExcelExportModal() {
    const modal = document.getElementById('matrix-excel-export-modal');
    if (!modal) {
      return;
    }

    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeMatrixExcelExportModalOnBackdrop(event) {
    if (event.target?.id !== 'matrix-excel-export-modal') {
      return;
    }

    closeMatrixExcelExportModal();
  }

  function confirmMatrixExcelExport(formatLabel) {
    const { tableId, title } = matrixExcelExportState;
    closeMatrixExcelExportModal();
    if (!tableId || !title) {
      return;
    }
    downloadMatrixExcel(tableId, title, formatLabel);
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeMatrixEmployeeModal();
    }
  });

  function createExportColGroup(widths) {
    const colGroup = document.createElement('colgroup');
    widths.forEach((width) => {
      const col = document.createElement('col');
      col.style.width = width;
      const numericWidth = parseInt(width, 10);
      if (Number.isFinite(numericWidth)) {
        col.setAttribute('width', String(numericWidth));
      }
      colGroup.appendChild(col);
    });
    return colGroup;
  }

  function applyExportColumnWidths(table, widths) {
    Array.from(table.rows).forEach((row) => {
      Array.from(row.cells).forEach((cell, index) => {
        if (cell.colSpan === widths.length) {
          return;
        }
        const width = widths[index];
        if (!width) {
          return;
        }
        cell.style.width = width;
        cell.style.minWidth = width;
        cell.style.maxWidth = width;
        const numericWidth = parseInt(width, 10);
        if (Number.isFinite(numericWidth)) {
          cell.setAttribute('width', String(numericWidth));
        }
      });
    });
  }

  function buildExcelExportTable(sourceTable, titleText, widths) {
    const exportTable = document.createElement('table');
    exportTable.appendChild(createExportColGroup(widths));

    const exportHead = document.createElement('thead');
    const titleRow = document.createElement('tr');
    const titleCell = document.createElement('th');
    titleCell.colSpan = widths.length;
    titleCell.textContent = titleText;
    titleRow.appendChild(titleCell);
    exportHead.appendChild(titleRow);

    const sourceHeaderRow = sourceTable.querySelector('thead tr');
    if (sourceHeaderRow) {
      exportHead.appendChild(sourceHeaderRow.cloneNode(true));
    }

    exportTable.appendChild(exportHead);

    const exportBody = document.createElement('tbody');
    sourceTable.querySelectorAll('tbody tr').forEach((row) => {
      exportBody.appendChild(row.cloneNode(true));
    });
    exportTable.appendChild(exportBody);

    return exportTable;
  }

  function downloadMatrixFile(content, fileName, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }

  function escapeExcelXml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;');
  }

  function escapeExcelFormulaString(value) {
    return String(value ?? '').replace(/"/g, '""');
  }

  function normalizeExcelCellText(cell) {
    return (cell.innerText || cell.textContent || '')
      .replace(/\r\n/g, '\n')
      .replace(/\n{3,}/g, '\n\n')
      .trim();
  }

  function getMatrixEmployeeAgeValue(ageValue, birthdayValue) {
    const parseAge = (value) => {
      const numeric = parseInt(String(value ?? '').replace(/[^\d]/g, ''), 10);
      return Number.isFinite(numeric) ? numeric : null;
    };
    const directAge = parseAge(ageValue);
    if (directAge !== null) {
      return String(directAge);
    }

    const birthday = String(birthdayValue ?? '').trim();
    if (!birthday) {
      return 'N/A';
    }

    const birthDate = new Date(birthday);
    if (Number.isNaN(birthDate.getTime())) {
      return 'N/A';
    }

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDifference = today.getMonth() - birthDate.getMonth();
    if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
      age -= 1;
    }

    return age >= 0 ? String(age) : 'N/A';
  }

  function collectMatrixEmployeeDetails(sourceTable) {
    const hasBenefitKeyword = (value, pattern) => pattern.test(String(value || ''));
    return Array.from(sourceTable.querySelectorAll('tbody tr')).map((row, rowIndex) => {
      const trigger = row.querySelector('.matrix-name-button');
      const fallbackName = normalizeExcelCellText(row.cells?.[0] || row);
      let employee = {};
      if (trigger) {
        try {
          employee = JSON.parse(trigger.dataset.matrixEmployeeEmployee || trigger.dataset.matrixEmployee || '{}') || {};
        } catch (error) {
          employee = {};
        }
      }

      const fullName = String(employee.full_name || trigger?.dataset.matrixEmployeeFullName || '').trim() || [employee.first_name, employee.last_name]
        .map((value) => String(value || '').trim())
        .filter(Boolean)
        .join(' ') || fallbackName || `Employee ${rowIndex + 1}`;
      const birthday = employee.birthday || trigger?.dataset.matrixEmployeeBirthday || '';
      const age = getMatrixEmployeeAgeValue(employee.age || trigger?.dataset.matrixEmployeeAge || '', birthday);

      const details = [
        ['Employee Name', fullName],
        ['Employee ID', employee.employee_id || 'N/A'],
        ['Middle Name', employee.middle_name || 'N/A'],
        ['First Name', employee.first_name || 'N/A'],
        ['Last Name', employee.last_name || 'N/A'],
        ['Age', age],
        ['Gender', employee.gender || 'N/A'],
        ['Account Number', employee.account_number || 'N/A'],
        ['Civil Status', employee.civil_status || 'N/A'],
        ['Contact Number', employee.contact_number || 'N/A'],
        ['Date of Birth', employee.birthday || 'N/A'],
        ['Address', employee.address || 'N/A'],
        ['Hired Date', employee.hired_date || 'N/A'],
        ['Position', employee.position || 'N/A'],
        ['Department', employee.department || 'N/A'],
        ['Classification', employee.classification || 'N/A'],
        ['Bachelor Degree', employee.bachelor_degree || 'N/A'],
        ['Master Degree', employee.master_degree || 'N/A'],
        ['Doctorate Degree', employee.doctorate_degree || 'N/A'],
        ['License', employee.license || 'N/A'],
        ['Registration Number', employee.registration_number || 'N/A'],
        ['Registration Date', employee.registration_date || 'N/A'],
        ['Valid Until', employee.valid_until || 'N/A'],
        ['Basic Salary', employee.salary || 'N/A'],
        ['Rate', employee.rate || 'N/A'],
        ['Rate Per Hour', employee.rate_per_hour || 'N/A'],
        ['COLA', employee.cola || 'N/A'],
        ['Status of Employment', employee.employment_status || 'N/A'],
        ['SSS', employee.sss || 'N/A'],
        ['TIN', employee.tin || 'N/A'],
        ['PhilHealth', employee.philhealth || 'N/A'],
        ['Pag-IBIG MID', employee.pagibig_mid || 'N/A'],
        ['Pag-IBIG RTN', employee.pagibig_rtn || 'N/A'],
      ];

      return {
        rowIndex,
        fullName,
        age,
        birthday,
        gender: employee.gender || trigger?.dataset.matrixEmployeeGender || 'N/A',
        hiredDate: employee.hired_date || trigger?.dataset.matrixEmployeeHiredDate || 'N/A',
        position: employee.position || trigger?.dataset.matrixEmployeePosition || 'N/A',
        rate: employee.rate_per_hour || employee.rate || trigger?.dataset.matrixEmployeeRate || 'N/A',
        employmentStatus: employee.employment_status || trigger?.dataset.matrixEmployeeEmploymentStatus || 'N/A',
        regularizationDate: employee.regularization_date || trigger?.dataset.matrixEmployeeRegularizationDate || '',
        spesBeneficiary: hasBenefitKeyword(employee.benefits_text, /\bSPES\b/i),
        jobStartBeneficiary: hasBenefitKeyword(employee.benefits_text, /\bjob[\s-]*start\b/i),
        details,
      };
    }).filter(Boolean);
  }

  function getMatrixHeaderRow(sourceTable, widths) {
    const rows = Array.from(sourceTable.querySelectorAll('thead tr'));
    return rows.find((row) => row.cells.length === widths.length) || rows[rows.length - 1] || null;
  }

  function escapeExcelWorksheetName(value) {
    const sanitized = String(value ?? '')
      .replace(/[\\\/\?\*\[\]:]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
    return (sanitized || 'Sheet').slice(0, 31);
  }

  function buildMatrixSheetNameMap(employeeDetails) {
    const usedNames = new Set();
    return new Map(employeeDetails.map((employee) => {
      const baseName = escapeExcelWorksheetName(`${employee.fullName} Details`);
      let sheetName = baseName;
      let counter = 2;
      while (usedNames.has(sheetName)) {
        const suffix = ` ${counter}`;
        sheetName = `${baseName.slice(0, Math.max(1, 31 - suffix.length))}${suffix}`;
        counter += 1;
      }
      usedNames.add(sheetName);
      return [employee.rowIndex, sheetName];
    }));
  }

  function buildMatrixDetailSheetXml(employee, sheetName) {
    const detailRows = employee.details.map(([label, value]) => `
      <Row ss:AutoFitHeight="1">
        <Cell ss:StyleID="detailLabel"><Data ss:Type="String">${escapeExcelXml(label)}</Data></Cell>
        <Cell ss:StyleID="detailValue"><Data ss:Type="String">${escapeExcelXml(value)}</Data></Cell>
      </Row>
    `).join('');

    return `
      <Worksheet ss:Name="${escapeExcelXml(sheetName)}">
        <Table>
          <Column ss:AutoFitWidth="0" ss:Width="150"/>
          <Column ss:AutoFitWidth="0" ss:Width="260"/>
          <Row ss:AutoFitHeight="0" ss:Height="26">
            <Cell ss:MergeAcross="1" ss:StyleID="detailTitle">
              <Data ss:Type="String">${escapeExcelXml(employee.fullName)}</Data>
            </Cell>
          </Row>
          ${detailRows}
        </Table>
      </Worksheet>
    `;
  }

  function getMatrixDoleProfile() {
    return {
      establishmentName: 'NORTHEASTERN COLLEGE',
      headOfficeAddress: 'VILLASIS, SANTIAGO CITY',
      branchOfficeAddress: '',
      principalActivity: '',
      ownerPresident: 'TOMAS C. BAUTISTA, PhD',
      contactNumber: '',
      emailAddress: 'ncpresidentsoffice@gmail.com',
      ownership: 'Corporation',
      noOfShifts: '',
      schedule: '',
    };
  }

  function getMatrixDoleSummary(employeeDetails) {
    const parseAge = (value) => {
      const numeric = parseInt(String(value ?? '').replace(/[^\d]/g, ''), 10);
      return Number.isFinite(numeric) ? numeric : null;
    };
    const normalizeGender = (value) => String(value ?? '').trim().toUpperCase();
    const summary = {
      below15: { male: 0, female: 0, total: 0 },
      age15to17: { male: 0, female: 0, total: 0 },
      age18to24: { male: 0, female: 0, total: 0 },
      age25to59: { male: 0, female: 0, total: 0 },
      age60to65: { male: 0, female: 0, total: 0 },
      males: 0,
      females: 0,
      total: employeeDetails.length,
    };

    employeeDetails.forEach((employee) => {
      const age = parseAge(employee.age);
      const gender = normalizeGender(employee.gender);
      const genderKey = gender.startsWith('M') ? 'male' : (gender.startsWith('F') ? 'female' : null);
      if (genderKey === 'male') summary.males += 1;
      if (genderKey === 'female') summary.females += 1;
      if (age === null) return;
      const bucket = age < 15
        ? summary.below15
        : age <= 17
          ? summary.age15to17
          : age <= 24
            ? summary.age18to24
            : age <= 59
              ? summary.age25to59
              : age <= 65
                ? summary.age60to65
                : null;
      if (!bucket) return;
      bucket.total += 1;
      if (genderKey) bucket[genderKey] += 1;
    });

    return summary;
  }

  function buildDoleExcelXml(employeeDetails, categoryLabel = '') {
    const profile = getMatrixDoleProfile();
    const summary = getMatrixDoleSummary(employeeDetails);
    const checkbox = () => '';
    const statusMark = (active) => active ? '✓' : '';
    const statusValue = (employee, label) => statusMark(employee.employmentStatus === label);
    const dateRegularizedValue = (employee) => employee.employmentStatus === 'Probationary' ? '' : (employee.regularizationDate || '');
    const dateOfRegularizationValue = (employee) => employee.employmentStatus === 'Probationary' ? (employee.regularizationDate || '') : '';
    const contractorRows = [1, 2, 3].map((index) => `
      <Row ss:AutoFitHeight="0" ss:Height="20">
        <Cell ss:Index="3" ss:StyleID="doleSectionCell"><Data ss:Type="Number">${index}</Data></Cell>
        <Cell ss:MergeAcross="5" ss:StyleID="doleSectionCell"></Cell>
        <Cell ss:MergeAcross="3" ss:StyleID="doleSectionCell"></Cell>
        <Cell ss:MergeAcross="2" ss:StyleID="doleSectionCell"></Cell>
        <Cell ss:MergeAcross="3" ss:StyleID="doleSectionCell"></Cell>
      </Row>
    `).join('');
    const principalRows = [1, 2].map((index, rowIndex) => `
      <Row ss:AutoFitHeight="0" ss:Height="20">
        <Cell ss:Index="2" ss:StyleID="${rowIndex === 1 ? 'doleNote' : 'doleNoteSpacer'}"><Data ss:Type="String">${rowIndex === 1 ? 'add separate sheet if needed' : ''}</Data></Cell>
        <Cell ss:StyleID="doleSectionCell"><Data ss:Type="Number">${index}</Data></Cell>
        <Cell ss:MergeAcross="5" ss:StyleID="doleSectionCell"></Cell>
        <Cell ss:MergeAcross="3" ss:StyleID="doleSectionCell"></Cell>
        <Cell ss:MergeAcross="2" ss:StyleID="doleSectionCell"></Cell>
      </Row>
    `).join('');
    const rows = [
      '<Row ss:AutoFitHeight="0" ss:Height="24"><Cell ss:MergeAcross="22" ss:StyleID="doleTitle"><Data ss:Type="String">ESTABLISHMENT WORKER&apos;S PROFILE</Data></Cell></Row>',
      categoryLabel ? `<Row ss:AutoFitHeight="0" ss:Height="20"><Cell ss:MergeAcross="22" ss:StyleID="doleCategory"><Data ss:Type="String">${escapeExcelXml(categoryLabel)}</Data></Cell></Row>` : '',
      `<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Name of Establishment:</Data></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleValueBold"><Data ss:Type="String">${escapeExcelXml(profile.establishmentName)}</Data></Cell><Cell ss:Index="10" ss:StyleID="doleLabel"><Data ss:Type="String">Kind of Ownership</Data></Cell><Cell ss:Index="15" ss:StyleID="doleMiniHeader"><Data ss:Type="String">Age Group</Data></Cell><Cell ss:StyleID="doleMiniHeader"><Data ss:Type="String">Male</Data></Cell><Cell ss:StyleID="doleMiniHeader"><Data ss:Type="String">Female</Data></Cell><Cell ss:StyleID="doleMiniHeader"><Data ss:Type="String">Total</Data></Cell><Cell ss:StyleID="doleMiniHeader"><Data ss:Type="String">No of Shifts</Data></Cell><Cell ss:StyleID="doleMiniHeader"><Data ss:Type="String">No.of Workers /shifts</Data></Cell></Row>`,
      `<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Head Office Address:</Data></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleValueBold"><Data ss:Type="String">${escapeExcelXml(profile.headOfficeAddress)}</Data></Cell><Cell ss:Index="10" ss:StyleID="doleCheck"><Data ss:Type="String">${checkbox(profile.ownership === 'Sole Proprietorship')}</Data></Cell><Cell ss:MergeAcross="3" ss:StyleID="doleValue"><Data ss:Type="String">Sole Proprietorship</Data></Cell><Cell ss:Index="15" ss:StyleID="doleSectionCell"><Data ss:Type="String">below 15</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.below15.male}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.below15.female}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.below15.total}</Data></Cell><Cell ss:StyleID="doleMiniHeader"><Data ss:Type="String">Schedule</Data></Cell><Cell ss:StyleID="doleSectionCell"></Cell></Row>`,
      `<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Brach Office Address:</Data></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleValue"><Data ss:Type="String">${escapeExcelXml(profile.branchOfficeAddress)}</Data></Cell><Cell ss:Index="10" ss:StyleID="doleCheck"><Data ss:Type="String">${checkbox(profile.ownership === 'Partnership')}</Data></Cell><Cell ss:MergeAcross="3" ss:StyleID="doleValue"><Data ss:Type="String">Partnership</Data></Cell><Cell ss:Index="15" ss:StyleID="doleSectionCell"><Data ss:Type="String">15-17 yrs.</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age15to17.male}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age15to17.female}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age15to17.total}</Data></Cell><Cell ss:StyleID="doleSectionCell"></Cell><Cell ss:StyleID="doleSectionCell"></Cell></Row>`,
      `<Row><Cell ss:Index="10" ss:StyleID="doleCheck"><Data ss:Type="String">${checkbox(profile.ownership === 'Corporation')}</Data></Cell><Cell ss:MergeAcross="3" ss:StyleID="doleValue"><Data ss:Type="String">Corporation</Data></Cell><Cell ss:Index="15" ss:StyleID="doleSectionCell"><Data ss:Type="String">18-24 yrs.</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age18to24.male}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age18to24.female}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age18to24.total}</Data></Cell><Cell ss:StyleID="doleSectionCell"></Cell><Cell ss:StyleID="doleSectionCell"></Cell></Row>`,
      `<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Principal Product/Main Activity:</Data></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleValue"><Data ss:Type="String">${escapeExcelXml(profile.principalActivity)}</Data></Cell><Cell ss:Index="10" ss:StyleID="doleCheck"><Data ss:Type="String">${checkbox(profile.ownership === 'Cooperative')}</Data></Cell><Cell ss:MergeAcross="3" ss:StyleID="doleValue"><Data ss:Type="String">Cooperative</Data></Cell><Cell ss:Index="15" ss:StyleID="doleSectionCell"><Data ss:Type="String">25-59 yrs.</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age25to59.male}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age25to59.female}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age25to59.total}</Data></Cell><Cell ss:StyleID="doleSectionCell"></Cell><Cell ss:StyleID="doleSectionCell"></Cell></Row>`,
      `<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Owner/President/CEO:</Data></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleValueBold"><Data ss:Type="String">${escapeExcelXml(profile.ownerPresident)}</Data></Cell><Cell ss:Index="15" ss:StyleID="doleSectionCell"><Data ss:Type="String">60-65 yrs.</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age60to65.male}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age60to65.female}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.age60to65.total}</Data></Cell><Cell ss:StyleID="doleSectionCell"></Cell><Cell ss:StyleID="doleSectionCell"></Cell></Row>`,
      `<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Contact Number:</Data></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleValue"><Data ss:Type="String">${escapeExcelXml(profile.contactNumber)}</Data></Cell><Cell ss:Index="15" ss:StyleID="doleSectionCell"><Data ss:Type="String">Total</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.males}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.females}</Data></Cell><Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${summary.total}</Data></Cell><Cell ss:StyleID="doleSectionCell"></Cell><Cell ss:StyleID="doleSectionCell"></Cell></Row>`,
      `<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Email Address:</Data></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleValueLink"><Data ss:Type="String">${escapeExcelXml(profile.emailAddress)}</Data></Cell></Row>`,
      '<Row><Cell ss:Index="2" ss:StyleID="doleLabel"><Data ss:Type="String">Contractors with existing agreements:</Data></Cell><Cell ss:StyleID="doleHeader"></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleHeader"><Data ss:Type="String">Name of Contractor/Subcontractor</Data></Cell><Cell ss:MergeAcross="3" ss:StyleID="doleHeader"><Data ss:Type="String">Registered Address</Data></Cell><Cell ss:MergeAcross="2" ss:StyleID="doleHeader"><Data ss:Type="String">No. of employees Deployed</Data></Cell><Cell ss:MergeAcross="3" ss:StyleID="doleHeader"><Data ss:Type="String">Service/s being provided by the</Data></Cell></Row>',
      contractorRows,
      '<Row/>',
      '<Row><Cell ss:Index="3" ss:StyleID="doleHeader"></Cell><Cell ss:MergeAcross="5" ss:StyleID="doleHeader"><Data ss:Type="String">Name of Principal Employers</Data></Cell><Cell ss:MergeAcross="3" ss:StyleID="doleHeader"><Data ss:Type="String">Office Address</Data></Cell><Cell ss:MergeAcross="2" ss:StyleID="doleHeader"><Data ss:Type="String">No. of employees Deployed</Data></Cell></Row>',
      principalRows,
      '<Row/>',
      '<Row ss:AutoFitHeight="0" ss:Height="16"><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">No.</Data></Cell><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">NAME OF EMPLYEE</Data></Cell><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">GENDER</Data></Cell><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">AGE</Data></Cell><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">RATE</Data></Cell><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">DATE HIRED</Data></Cell><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">DATE REGULARIZED</Data></Cell><Cell ss:StyleID="doleHeader" ss:MergeDown="1"><Data ss:Type="String">POSITION</Data></Cell><Cell ss:MergeAcross="14" ss:StyleID="doleHeader"><Data ss:Type="String">STATUS OF EMPLOYMENT</Data></Cell></Row>',
      '<Row><Cell ss:Index="9" ss:StyleID="doleStatusHeader"><Data ss:Type="String">Regular</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Probationary</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Fixed term</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Casual</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Regular-Seasonal</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Contract workers</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">PWD</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Foreign National</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Apprent</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Learners</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Trainee (DTS)</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Trainee (DTP)</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">SPES Beneficiaries</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">Job start Beneficiaries</Data></Cell><Cell ss:StyleID="doleStatusHeader"><Data ss:Type="String">DATE OF REGULARIZATION</Data></Cell></Row>',
    ];

    employeeDetails.forEach((employee, index) => {
      rows.push(`
        <Row>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="Number">${index + 1}</Data></Cell>
          <Cell ss:StyleID="doleCell"><Data ss:Type="String">${escapeExcelXml(employee.fullName)}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${escapeExcelXml(employee.gender)}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${escapeExcelXml(employee.age)}</Data></Cell>
          <Cell ss:StyleID="doleCell"><Data ss:Type="String">${escapeExcelXml(employee.rate)}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${escapeExcelXml(employee.hiredDate)}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${escapeExcelXml(dateRegularizedValue(employee))}</Data></Cell>
          <Cell ss:StyleID="doleCell"><Data ss:Type="String">${escapeExcelXml(employee.position)}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${statusValue(employee, 'Permanent')}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${statusValue(employee, 'Probationary')}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${statusMark(employee.spesBeneficiary)}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${statusMark(employee.jobStartBeneficiary)}</Data></Cell>
          <Cell ss:StyleID="doleCellCenter"><Data ss:Type="String">${escapeExcelXml(dateOfRegularizationValue(employee))}</Data></Cell>
        </Row>
      `);
    });

    return [
      '<' + '?xml version="1.0"?>',
      '<' + '?mso-application progid="Excel.Sheet"?>',
      '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"',
      ' xmlns:o="urn:schemas-microsoft-com:office:office"',
      ' xmlns:x="urn:schemas-microsoft-com:office:excel"',
      ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"',
      ' xmlns:html="http://www.w3.org/TR/REC-html40">',
      '<Styles>',
      '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders/><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#111827"/><Interior/><NumberFormat/><Protection/></Style>',
      '<Style ss:ID="doleTitle"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1"/></Style>',
      '<Style ss:ID="doleLabel"><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#A8A29E"/></Borders><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1"/></Style>',
      '<Style ss:ID="doleCategory"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/></Style>',
      '<Style ss:ID="doleValue"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/></Borders></Style>',
      '<Style ss:ID="doleValueBold"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/></Borders><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1"/></Style>',
      '<Style ss:ID="doleValueLink"><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/></Borders><Font ss:FontName="Calibri" ss:Size="10" ss:Underline="Single" ss:Color="#1D4ED8"/></Style>',
      '<Style ss:ID="doleCheck"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/></Borders><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1"/></Style>',
      '<Style ss:ID="doleMiniHeader"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/></Borders><Font ss:FontName="Calibri" ss:Size="9" ss:Bold="1"/></Style>',
      '<Style ss:ID="doleHeader"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/></Borders><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1"/><Interior ss:Color="#E7E5E4" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="doleStatusHeader"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/></Borders><Font ss:FontName="Calibri" ss:Size="8"/></Style>',
      '<Style ss:ID="doleSectionCell"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#111827"/></Borders></Style>',
      '<Style ss:ID="doleNoteSpacer"><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Borders/><Font ss:FontName="Calibri" ss:Size="10" ss:Italic="1"/></Style>',
      '<Style ss:ID="doleNote"><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Borders/><Font ss:FontName="Calibri" ss:Size="10" ss:Italic="1"/></Style>',
      '<Style ss:ID="doleCell"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/></Borders></Style>',
      '<Style ss:ID="doleCellCenter"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D6D3D1"/></Borders></Style>',
      '</Styles>',
      '<Worksheet ss:Name="Sheet1"><Table>',
      '<Column ss:AutoFitWidth="0" ss:Width="38"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="240"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="58"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="50"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="92"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="88"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="92"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="128"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="45"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="52"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="52"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="60"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="68"/>',
      '<Column ss:AutoFitWidth="0" ss:Width="92"/>',
      rows.join(''),
      '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><DisplayGridlines/></WorksheetOptions></Worksheet>',
      '</Workbook>',
    ].join('');
  }

  function buildMatrixExcelXml(sourceTable, worksheetName, titleText, widths, employeeDetails = []) {
    const rows = [];
    const detailSheetNameMap = buildMatrixSheetNameMap(employeeDetails);
    rows.push(`
      <Row ss:AutoFitHeight="0" ss:Height="28">
        <Cell ss:MergeAcross="${widths.length - 1}" ss:StyleID="title">
          <Data ss:Type="String">${escapeExcelXml(titleText)}</Data>
        </Cell>
      </Row>
    `);

    const headerRow = getMatrixHeaderRow(sourceTable, widths);
    if (headerRow) {
      const headerCells = Array.from(headerRow.cells).map((cell) => `
        <Cell ss:StyleID="header">
          <Data ss:Type="String">${escapeExcelXml(normalizeExcelCellText(cell))}</Data>
        </Cell>
      `).join('');
      rows.push(`<Row ss:AutoFitHeight="1">${headerCells}</Row>`);
    }

    Array.from(sourceTable.querySelectorAll('tbody tr')).forEach((row, rowIndex) => {
      const cells = Array.from(row.cells).map((cell, cellIndex) => {
        const employee = employeeDetails.find((item) => item.rowIndex === rowIndex);
        const detailSheetName = employee ? detailSheetNameMap.get(rowIndex) : '';
        const cellText = normalizeExcelCellText(cell);
        const styleId = cellIndex === 0
          ? (employee ? 'nameLink' : 'name')
          : (rowIndex % 2 === 0 ? 'bodyAlt' : 'body');
        const formulaAttr = cellIndex === 0 && employee
          ? ` ss:Formula="${escapeExcelXml(`=HYPERLINK("#'${escapeExcelFormulaString(detailSheetName)}'!A1","${escapeExcelFormulaString(cellText)}")`)}"`
          : '';
        return `
          <Cell ss:StyleID="${styleId}"${formulaAttr}>
            <Data ss:Type="String">${escapeExcelXml(cellText)}</Data>
          </Cell>
        `;
      }).join('');

      rows.push(`<Row ss:AutoFitHeight="1">${cells}</Row>`);
    });

    const columns = widths.map((width) => {
      const numericWidth = parseInt(width, 10) || 120;
      return `<Column ss:AutoFitWidth="0" ss:Width="${numericWidth}"/>`;
    }).join('');

    const detailWorksheets = employeeDetails.map((employee) =>
      buildMatrixDetailSheetXml(employee, detailSheetNameMap.get(employee.rowIndex))
    ).join('');

    const xmlParts = [
      '<' + '?xml version="1.0"?>',
      '<' + '?mso-application progid="Excel.Sheet"?>',
      '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"',
      ' xmlns:o="urn:schemas-microsoft-com:office:office"',
      ' xmlns:x="urn:schemas-microsoft-com:office:excel"',
      ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"',
      ' xmlns:html="http://www.w3.org/TR/REC-html40">',
      '<Styles>',
      '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Top" ss:WrapText="1"/><Borders/>',
      '<Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1F2937"/><Interior/><NumberFormat/><Protection/></Style>',
      '<Style ss:ID="title"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/></Borders>',
      '<Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#FFFFFF"/>',
      '<Interior ss:Color="#1D4ED8" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/></Borders>',
      '<Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#0F172A"/>',
      '<Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="body"><Alignment ss:Vertical="Top" ss:WrapText="1"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders>',
      '<Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="bodyAlt"><Alignment ss:Vertical="Top" ss:WrapText="1"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders>',
      '<Interior ss:Color="#EEF6FF" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="name"><Alignment ss:Vertical="Top" ss:WrapText="0"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#94A3B8"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders>',
      '<Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#111827"/>',
      '<Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="nameLink"><Alignment ss:Vertical="Top" ss:WrapText="0"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#94A3B8"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders>',
      '<Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Underline="Single" ss:Color="#1D4ED8"/>',
      '<Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="detailTitle"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/></Borders>',
      '<Font ss:FontName="Calibri" ss:Size="13" ss:Bold="1" ss:Color="#FFFFFF"/>',
      '<Interior ss:Color="#1D4ED8" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="detailLabel"><Alignment ss:Vertical="Center" ss:WrapText="1"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders>',
      '<Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#111827"/>',
      '<Interior ss:Color="#F5F7FA" ss:Pattern="Solid"/></Style>',
      '<Style ss:ID="detailValue"><Alignment ss:Vertical="Center" ss:WrapText="1"/>',
      '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders>',
      '<Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/></Style>',
      '</Styles>',
      `<Worksheet ss:Name="${escapeExcelXml(worksheetName).slice(0, 31)}">`,
      '<Table>',
      columns,
      rows.join(''),
      '</Table>',
      '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><DisplayGridlines/><FreezePanes/><FrozenNoSplit/><SplitHorizontal>2</SplitHorizontal><TopRowBottomPane>2</TopRowBottomPane></WorksheetOptions>',
      '</Worksheet>',
      detailWorksheets,
      '</Workbook>',
    ];

    return xmlParts.join('');
  }

  function styleMatrixExcelTable(table) {
    table.style.width = '100%';
    table.style.borderCollapse = 'collapse';
    table.style.tableLayout = 'fixed';
    table.style.fontFamily = 'Calibri, Arial, sans-serif';
    table.style.fontSize = '11pt';
    table.style.color = '#1f2937';
    table.style.backgroundColor = '#ffffff';

    table.querySelectorAll('ul').forEach((list) => {
      list.style.margin = '0';
      list.style.paddingLeft = '0';
      list.style.marginLeft = '2pt';
      list.style.listStylePosition = 'inside';
    });

    table.querySelectorAll('li').forEach((item) => {
      item.style.margin = '0 0 2pt 0';
      item.style.padding = '0';
    });

    table.querySelectorAll('td div, td span, td strong, th div, th span').forEach((node) => {
      node.style.wordBreak = 'break-word';
      node.style.overflowWrap = 'anywhere';
    });

    const rows = table.querySelectorAll('tr');
    rows.forEach((row, rowIndex) => {
      row.querySelectorAll('th, td').forEach((cell) => {
        cell.style.border = '1px solid #cbd5e1';
        cell.style.padding = '7pt 6pt';
        cell.style.verticalAlign = 'top';
        cell.style.wordWrap = 'break-word';
        cell.style.overflowWrap = 'anywhere';
        cell.style.lineHeight = '1.35';
        cell.style.whiteSpace = 'normal';
      });

      if (rowIndex === 0) {
        row.querySelectorAll('th').forEach((cell) => {
          cell.style.backgroundColor = '#1d4ed8';
          cell.style.color = '#ffffff';
          cell.style.fontWeight = '700';
          cell.style.textAlign = 'center';
          cell.style.verticalAlign = 'middle';
          cell.style.border = '1px solid #1e3a8a';
          cell.style.fontSize = '14pt';
          cell.style.padding = '12pt 10pt';
        });
      }

      if (rowIndex === 1) {
        row.querySelectorAll('th').forEach((cell) => {
          cell.style.backgroundColor = '#dbeafe';
          cell.style.color = '#0f172a';
          cell.style.fontWeight = '700';
          cell.style.textAlign = 'center';
          cell.style.verticalAlign = 'middle';
          cell.style.border = '1px solid #93c5fd';
        });
      }

      if (rowIndex > 1) {
        const fillColor = rowIndex % 2 === 0 ? '#f8fafc' : '#eef6ff';
        row.querySelectorAll('td').forEach((cell) => {
          cell.style.backgroundColor = fillColor;
        });
      }

      if (row.cells[0]) {
        row.cells[0].style.borderRight = '2px solid #94a3b8';
        row.cells[0].style.width = '260px';
        row.cells[0].style.minWidth = '260px';
        row.cells[0].style.maxWidth = '260px';
        row.cells[0].style.whiteSpace = rowIndex === 0 ? 'normal' : 'nowrap';
        row.cells[0].style.wordBreak = 'normal';
        row.cells[0].style.overflowWrap = 'normal';
        row.cells[0].setAttribute('width', '260');
      }

      if (row.cells[1]) {
        row.cells[1].style.borderRight = '2px solid #cbd5e1';
        row.cells[1].style.width = '360px';
        row.cells[1].style.minWidth = '360px';
        row.cells[1].style.maxWidth = '360px';
        row.cells[1].setAttribute('width', '360');
      }
    });
  }

  function buildMatrixExcelHtml(tableMarkup, title) {
    return `
      <html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="utf-8">
        <meta name="ProgId" content="Excel.Sheet">
        <meta name="Generator" content="Microsoft Excel 15">
        <title>${title}</title>
        <style>
          body {
            margin: 0;
            padding: 18px;
            background: #e2e8f0;
            font-family: Calibri, Arial, sans-serif;
          }

          .sheet-wrap {
            background: #ffffff;
            padding: 10px;
            border: 1px solid #bfdbfe;
          }

          table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            mso-table-layout-alt: fixed;
          }

          br {
            mso-data-placement: same-cell;
          }

          col {
            mso-width-source: userset;
          }

          td, th {
            mso-number-format: "\\@";
          }
        </style>
      </head>
      <body>
        <div class="sheet-wrap">
          ${tableMarkup}
        </div>
      </body>
      </html>
    `;
  }

  function downloadMatrixWord(tableId, title) {
    const table = document.getElementById(tableId);
    if (!table) {
      return;
    }

    const wordTable = table.cloneNode(true);
    const wordTitleRow = wordTable.createTHead().insertRow(0);
    const wordTitleCell = document.createElement('th');
    wordTitleCell.colSpan = 7;
    wordTitleCell.textContent = '15. Matrix list of academic non-teaching personnel including (Registrar, Librarian, Guidance Counselor, Researcher, etc.)';
    wordTitleCell.style.border = '1px solid #000';
    wordTitleCell.style.padding = '6pt 5pt';
    wordTitleCell.style.textAlign = 'left';
    wordTitleCell.style.fontWeight = '700';
    wordTitleRow.appendChild(wordTitleCell);
    const exportWidths = ['12%', '30%', '14%', '10%', '11%', '10%', '13%'];
    wordTable.insertBefore(createExportColGroup(exportWidths), wordTable.firstChild);
    applyExportColumnWidths(wordTable, exportWidths);
    wordTable.style.width = '100%';
    wordTable.style.borderCollapse = 'collapse';
    wordTable.style.tableLayout = 'fixed';
    wordTable.querySelectorAll('ul').forEach((list) => {
      list.style.margin = '0';
      list.style.paddingLeft = '0';
      list.style.marginLeft = '2pt';
      list.style.listStylePosition = 'inside';
    });
    wordTable.querySelectorAll('li').forEach((item) => {
      item.style.margin = '0 0 2pt 0';
      item.style.padding = '0';
    });

    const html = `
      <html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:w="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="utf-8">
        <title>${title}</title>
        <!--[if gte mso 9]>
        <xml>
          <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>90</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
          </w:WordDocument>
        </xml>
        <![endif]-->
        <style>
          @page Section1 {
            size: 13in 8.5in;
            mso-page-orientation: landscape;
            margin: 0.35in;
          }
          body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
          }
          .word-section {
            page: Section1;
          }
          .matrix-title {
            font-size: 12pt;
            font-weight: 700;
            margin: 0 0 8pt 0;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
          }
          th, td {
            border: 1px solid #000;
            padding: 6pt 5pt;
            vertical-align: top;
            word-wrap: break-word;
          }
          th {
            text-align: center;
            font-weight: 700;
          }
          ul {
            margin: 0;
            padding-left: 0;
            margin-left: 2pt;
            list-style-position: inside;
          }
          li {
            margin: 0 0 2pt 0;
            padding: 0;
          }
        </style>
      </head>
      <body>
        <div class="word-section">
          <p class="matrix-title">${title}</p>
          ${wordTable.outerHTML}
        </div>
      </body>
      </html>
    `;

    downloadMatrixFile(html, `${tableId}.doc`, 'application/msword');
  }


  function downloadMatrixExcel(tableId, title, formatLabel = 'CHED') {
    const table = document.getElementById(tableId);
    if (!table) {
      return;
    }

    const exportWidths = ['260px', '360px', '170px', '145px', '165px', '170px', '205px'];
    const employeeDetails = collectMatrixEmployeeDetails(table);
    if (String(formatLabel).toUpperCase() === 'DOLE') {
      const doleXml = buildDoleExcelXml(employeeDetails, title);
      downloadMatrixFile(doleXml, `${tableId}-dole.xls`, 'application/vnd.ms-excel');
      return;
    }
    const xml = buildMatrixExcelXml(
      table,
      `${title} - ${formatLabel}`,
      '15. Matrix list of academic non-teaching personnel including (Registrar, Librarian, Guidance Counselor, Researcher, etc.)',
      exportWidths,
      employeeDetails
    );

    const safeFormat = String(formatLabel || 'CHED').trim() || 'CHED';
    downloadMatrixFile(xml, `${tableId}-${safeFormat.toLowerCase()}.xls`, 'application/vnd.ms-excel');
  }
</script>

</body>
</html>
