<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PeopleHub - Insert Employees</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .employee-import-icon { position: relative; isolation: isolate; }
    .employee-import-icon-ring { position: absolute; inset: -7px; border: 3px solid transparent; border-top-color: #10b981; border-right-color: #a7f3d0; border-radius: 999px; opacity: 0; }
    .employee-import-icon i { position: absolute; left: 50%; top: 50%; opacity: 0; transform: translate(-50%, -50%) scale(.65); transition: opacity .2s ease, transform .3s cubic-bezier(.22,.9,.2,1); }
    .employee-import-icon .employee-import-idle { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    .employee-import-zone.has-file .employee-import-idle,
    .employee-import-zone.is-importing .employee-import-icon i { opacity: 0; transform: translate(-50%, -50%) scale(.65); }
    .employee-import-zone.has-file:not(.is-importing) .employee-import-file,
    .employee-import-zone.stage-validating .employee-import-check,
    .employee-import-zone.stage-reading .employee-import-reading,
    .employee-import-zone.stage-importing .employee-import-users,
    .employee-import-zone.stage-saving .employee-import-saving { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    .employee-import-zone.is-importing .employee-import-icon-ring { opacity: 1; animation: employee-import-spin .9s linear infinite; }
    .employee-import-zone.stage-reading .employee-import-reading { animation: employee-import-pulse .9s ease-in-out infinite; }
    .employee-import-progress-bar { transition: width .6s cubic-bezier(.22,.9,.2,1); }
    @keyframes employee-import-spin { to { transform: rotate(360deg); } }
    @keyframes employee-import-pulse { 50% { transform: translate(-50%, -50%) scale(1.12); } }
    @media (max-width: 640px) {
      .employee-import-zone { min-height: 0 !important; padding: 1rem !important; }
      .employee-import-zone-content { display: grid; grid-template-columns: 2.75rem minmax(0,1fr); gap: .2rem .85rem; width: 100%; text-align: left; }
      .employee-import-icon { grid-row: 1 / 4; width: 2.75rem !important; height: 2.75rem !important; border-radius: .8rem !important; }
      .employee-import-title, .employee-import-help, [data-employee-file-name] { grid-column: 2; margin-top: 0 !important; }
      .employee-import-title { font-size: .9rem !important; }
      .employee-import-help { font-size: .75rem !important; }
      .employee-import-progress { margin-top: .85rem !important; }
      .employee-import-submit { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
      .employee-import-icon-ring, .employee-import-icon i, .employee-import-progress-bar { animation: none !important; transition: none !important; }
    }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#f7fafc_50%,#eefbf6_100%)] text-slate-800">
  <div class="flex min-h-screen">
    @include('components.adminSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
      <header class="relative z-40 px-4 py-4 md:px-8 md:py-5">
        <div class="relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,#020617_0%,#111827_62%,#064e3b_100%)] px-6 py-6 text-white shadow-[0_24px_60px_rgba(3,19,29,0.34)] md:px-8">
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(45,212,191,0.15),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(110,231,183,0.14),transparent_34%)]"></div>
          <div class="relative flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-50">
                <i class="fa-solid fa-file-excel"></i>
                Employee Import
              </div>
              <h1 class="mt-4 text-3xl font-black tracking-tight md:text-4xl">Insert Employees from Excel</h1>
              <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85">Upload an employee spreadsheet securely for processing in the HRIS workspace.</p>
            </div>
            <a href="{{ route('admin.adminEmployee', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/15 bg-white/10 px-5 py-3 text-sm font-semibold transition hover:bg-white/20">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Employees
            </a>
          </div>
        </div>
      </header>

      <div class="px-4 pb-10 md:px-8">
        <section class="mx-auto max-w-4xl rounded-[2rem] border border-white/80 bg-white/95 p-6 shadow-[0_22px_55px_rgba(15,23,42,0.08)] md:p-8">
          @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
              <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
            </div>
          @endif

          @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-700">
              <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $errors->first() }}
            </div>
          @endif

          @if (session('import_warnings'))
            <details open class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 text-sm text-amber-900">
              <summary class="cursor-pointer px-5 py-4 font-bold">{{ session('import_skipped_count', count(session('import_warnings'))) }} row(s) were not imported</summary>
              <div class="overflow-x-auto border-t border-amber-200 bg-white">
                <table class="w-full min-w-[680px] text-left">
                  <thead class="bg-amber-50 text-xs uppercase tracking-wider text-amber-800">
                    <tr>
                      <th class="px-4 py-3">Excel row</th>
                      <th class="px-4 py-3">Employee name</th>
                      <th class="px-4 py-3">Employee ID</th>
                      <th class="px-4 py-3">Reason not imported</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-amber-100 text-slate-700">
                    @foreach (session('import_warnings') as $warning)
                      @php
                        $isDetailedWarning = is_array($warning);
                        $rowNumber = $isDetailedWarning ? ($warning['row'] ?? '—') : '—';
                        $employeeName = $isDetailedWarning ? ($warning['name'] ?? '—') : '—';
                        $employeeId = $isDetailedWarning ? ($warning['employee_id'] ?? '—') : '—';
                        $reason = $isDetailedWarning ? ($warning['reason'] ?? 'Unknown error') : $warning;
                      @endphp
                      <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3 font-bold text-amber-700">{{ $rowNumber }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $employeeName }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $employeeId }}</td>
                        <td class="px-4 py-3 text-rose-700">{{ $reason }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @if (session('import_skipped_count', 0) > count(session('import_warnings')))
                <p class="border-t border-amber-200 px-5 py-3 font-semibold">Showing the first {{ count(session('import_warnings')) }} row errors.</p>
              @endif
            </details>
          @endif

          <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Upload Area</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">Choose an employee spreadsheet</h2>
            @php($requiredEmployeeFileBase = '201-file-'.now('Asia/Manila')->format('M-Y'))
            <p class="mt-2 text-sm leading-6 text-slate-500">Accepted formats are `.xlsx` and `.csv`, with a maximum file size of 10 MB.</p>
            <div class="mt-4 inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
              <i class="fa-solid fa-tag"></i>
              Required filename: <strong>{{ $requiredEmployeeFileBase }}.xlsx</strong> or <strong>{{ $requiredEmployeeFileBase }}.csv</strong>
            </div>
            <div class="mt-4 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">
              <strong>Worksheet:</strong> <code>201 file</code> or <code>Employee Information</code> is preferred. If neither tab exists, the importer uses the first worksheet. Required columns are <code>Name</code> and <code>ID number</code>. Each valid row creates an internal employee record immediately. Directly imported employees are not placed in the applicant process, and no temporary-password email is sent. Other employee, government ID, salary, license, and education columns are saved too.
            </div>
          </div>

          <form id="employee_import_form" method="POST" action="{{ route('admin.employeeImport.upload') }}" enctype="multipart/form-data" class="mt-7">
            @csrf
            @if (request()->filled('tab_session'))
              <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
            @endif

            <label for="employee_file" class="employee-import-zone group relative flex min-h-72 cursor-pointer flex-col items-center justify-center rounded-[1.75rem] border-2 border-dashed border-emerald-200 bg-emerald-50/50 px-6 py-10 text-center transition hover:border-emerald-400 hover:bg-emerald-50">
              <span class="employee-import-zone-content flex flex-col items-center">
                <span class="employee-import-icon flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-2xl text-emerald-700 transition group-hover:scale-105">
                  <span class="employee-import-icon-ring"></span>
                  <i class="employee-import-idle fa-solid fa-file-arrow-up"></i>
                  <i class="employee-import-file fa-solid fa-file-excel"></i>
                  <i class="employee-import-check fa-solid fa-list-check"></i>
                  <i class="employee-import-reading fa-solid fa-magnifying-glass-chart"></i>
                  <i class="employee-import-users fa-solid fa-users-gear"></i>
                  <i class="employee-import-saving fa-solid fa-shield-halved"></i>
                </span>
                <span class="employee-import-title mt-5 text-lg font-black text-slate-900">Drop your Excel file here</span>
                <span class="employee-import-help mt-2 text-sm text-slate-500">or click to browse from your computer</span>
                <span data-employee-file-name class="mt-4 hidden max-w-full truncate rounded-full bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm"></span>
              </span>
              <div id="employee_import_progress" class="employee-import-progress mt-6 hidden w-full max-w-lg rounded-2xl border border-emerald-100 bg-white/95 p-4 text-left shadow-sm" aria-live="polite">
                <div class="flex items-center justify-between gap-3 text-xs font-bold">
                  <span id="employee_import_status" class="text-slate-700">Ready to import</span>
                  <span id="employee_import_percent" class="tabular-nums text-emerald-700">0%</span>
                </div>
                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
                  <div id="employee_import_progress_bar" class="employee-import-progress-bar h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400" style="width:0%"></div>
                </div>
                <p id="employee_import_detail" class="mt-2 text-xs text-slate-500">Keep this page open while employee records are processed.</p>
              </div>
              <input id="employee_file" name="employee_file" type="file" accept=".xlsx,.csv" required data-required-base="{{ $requiredEmployeeFileBase }}" class="absolute inset-0 cursor-pointer opacity-0">
            </label>

            <div class="mt-6 flex justify-end">
              <button id="employee_import_submit" type="submit" class="employee-import-submit inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                Upload Employee File
              </button>
            </div>
          </form>
        </section>
      </div>
    </main>
  </div>

  <script>
    const employeeImportForm = document.getElementById('employee_import_form');
    const employeeFileInput = document.getElementById('employee_file');
    const employeeFileName = document.querySelector('[data-employee-file-name]');
    const employeeImportZone = document.querySelector('.employee-import-zone');
    const employeeImportProgress = document.getElementById('employee_import_progress');
    const employeeImportStatus = document.getElementById('employee_import_status');
    const employeeImportPercent = document.getElementById('employee_import_percent');
    const employeeImportProgressBar = document.getElementById('employee_import_progress_bar');
    const employeeImportDetail = document.getElementById('employee_import_detail');
    const employeeImportSubmit = document.getElementById('employee_import_submit');
    let employeeImportStarted = false;
    let employeeImportTimers = [];

    const clearEmployeeImportTimers = () => {
      employeeImportTimers.forEach((timer) => window.clearTimeout(timer));
      employeeImportTimers = [];
    };

    const setEmployeeImportStage = (stage, status, percent, detail) => {
      employeeImportZone?.classList.remove('stage-validating', 'stage-reading', 'stage-importing', 'stage-saving');
      if (stage) employeeImportZone?.classList.add(stage);
      employeeImportProgress?.classList.remove('hidden');
      if (employeeImportStatus) employeeImportStatus.textContent = status;
      if (employeeImportPercent) employeeImportPercent.textContent = `${percent}%`;
      if (employeeImportProgressBar) employeeImportProgressBar.style.width = `${percent}%`;
      if (employeeImportDetail) employeeImportDetail.textContent = detail;
    };

    const queueEmployeeImportStage = (delay, stage, status, percent, detail) => {
      employeeImportTimers.push(window.setTimeout(
        () => setEmployeeImportStage(stage, status, percent, detail),
        delay
      ));
    };

    employeeFileInput?.addEventListener('change', () => {
      const file = employeeFileInput.files?.[0];
      if (!employeeFileName) return;
      clearEmployeeImportTimers();
      employeeImportStarted = false;
      employeeImportZone?.classList.remove('is-importing', 'stage-validating', 'stage-reading', 'stage-importing', 'stage-saving');
      employeeImportZone?.classList.toggle('has-file', Boolean(file));
      employeeImportProgress?.classList.add('hidden');
      if (employeeImportProgressBar) employeeImportProgressBar.style.width = '0%';
      if (employeeImportSubmit) {
        employeeImportSubmit.disabled = false;
        employeeImportSubmit.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload Employee File';
      }
      const requiredBase = employeeFileInput.dataset.requiredBase || '';
      const uploadedBase = file ? file.name.replace(/\.[^.]+$/, '') : '';
      const isValidName = !file || uploadedBase.toLowerCase() === requiredBase.toLowerCase();
      employeeFileInput.setCustomValidity(isValidName ? '' : `Rename the file to ${requiredBase}.xlsx or ${requiredBase}.csv.`);
      employeeFileName.textContent = file
        ? (isValidName ? file.name : `Invalid name: ${file.name}`)
        : '';
      employeeFileName.classList.toggle('text-rose-700', !isValidName);
      employeeFileName.classList.toggle('text-emerald-700', isValidName);
      employeeFileName.classList.toggle('hidden', !file);
    });

    employeeImportForm?.addEventListener('submit', (event) => {
      if (employeeImportStarted) {
        event.preventDefault();
        return;
      }

      if (!employeeImportForm.checkValidity()) return;
      employeeImportStarted = true;
      clearEmployeeImportTimers();
      employeeImportZone?.classList.add('is-importing');
      employeeImportSubmit.disabled = true;
      employeeImportSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importing employees...';

      setEmployeeImportStage('stage-validating', 'Validating spreadsheet', 12, 'Checking the filename, format, and required columns.');
      queueEmployeeImportStage(550, 'stage-reading', 'Reading worksheet', 34, 'Opening the employee worksheet and mapping its columns.');
      queueEmployeeImportStage(1450, 'stage-importing', 'Importing employee records', 62, 'Creating or updating valid employee accounts row by row.');
      queueEmployeeImportStage(3000, 'stage-importing', 'Importing employee records', 78, 'Processing employee, government, salary, license, and education details.');
      queueEmployeeImportStage(5200, 'stage-saving', 'Saving import results', 92, 'Finalizing records and securely storing the source spreadsheet.');
    });
  </script>
</body>
</html>
