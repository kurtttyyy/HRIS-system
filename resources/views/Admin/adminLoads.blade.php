<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Loads</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .loads-file-active {
      border-color: rgb(125 211 252);
      background: linear-gradient(180deg, rgba(224, 242, 254, 0.85), rgba(255, 255, 255, 0.96));
      box-shadow: 0 18px 38px rgba(14, 165, 233, 0.12);
    }
    .loads-progress-bar {
      transition: width 0.45s ease;
    }
    .loads-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .loads-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .loads-reveal.is-visible {
      animation: loads-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--loads-delay, 0ms);
    }
    .loads-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .loads-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .loads-icon-pop {
      animation: loads-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--loads-delay, 0ms);
    }
    .loads-row-motion {
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .loads-row-motion:hover {
      transform: translateX(4px);
      border-color: rgb(186 230 253);
      box-shadow: inset 3px 0 0 rgba(14, 165, 233, 0.55), 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    @keyframes loads-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes loads-pop-in {
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
      .loads-reveal,
      .loads-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .loads-card-motion,
      .loads-row-motion {
        transition: none;
      }
      .loads-card-motion:hover,
      .loads-row-motion:hover {
        transform: none;
      }
    }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#eef4ff_45%,#f8fafc_100%)] text-slate-800">
<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    @php
      $loadsFiles = $loadsFiles ?? collect();
      $loadsSummary = $loadsSummary ?? collect();
      $uploadedCount = $loadsFiles->count();
      $scannedCount = $loadsFiles->filter(fn ($file) => in_array(strtolower((string) ($file->status ?? '')), ['scanned', 'processed']))->count();
      $pendingCount = max($uploadedCount - $scannedCount, 0);
      $latestUpload = $loadsFiles->sortByDesc(fn ($file) => optional($file->uploaded_at)?->timestamp ?? 0)->first();
    @endphp
    <div id="admin-loads-page" class="p-4 md:p-8 pt-10 space-y-6">
      <section class="loads-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] px-6 py-6 shadow-[0_24px_60px_rgba(3,19,29,0.34)] md:px-8" style="--loads-delay: 0ms;">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="absolute -left-8 top-6 h-24 w-24 rounded-full bg-cyan-300/10 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-32 w-32 translate-x-10 -translate-y-8 rounded-full bg-emerald-300/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-50">
              <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
              Loads Center
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">Uploaded and Scanned Loads</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85 md:text-base">
              Upload teaching-load files, track scan readiness, and move from raw source sheets to view-ready load summaries in one workspace.
            </p>
            <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-emerald-50/80">
              <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ now()->format('l, F j, Y') }}</span>
              <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $uploadedCount }} uploaded file(s)</span>
              <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $scannedCount }} scanned</span>
            </div>
          </div>

          <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[420px]">
            <div class="loads-card-motion loads-reveal rounded-2xl border border-white/10 bg-white/8 px-4 py-4 shadow-sm backdrop-blur" style="--loads-delay: 70ms;">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-50/70">Latest Upload</p>
              <p class="mt-2 text-sm font-semibold text-white">{{ $latestUpload?->original_name ?? 'No file yet' }}</p>
              <p class="mt-1 text-xs text-emerald-50/75">{{ optional($latestUpload?->uploaded_at)->format('M d, Y h:i A') ?? 'Waiting for first upload' }}</p>
            </div>
            <div class="loads-card-motion loads-reveal rounded-2xl border border-white/10 bg-white/8 px-4 py-4 shadow-sm backdrop-blur" style="--loads-delay: 100ms;">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-50/70">Queue Status</p>
              <p class="mt-2 text-sm font-semibold text-white">{{ $pendingCount }} pending scan</p>
              <p class="mt-1 text-xs text-emerald-50/75">Upload is active. Scan remains a visual placeholder for now.</p>
            </div>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="loads-card-motion loads-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--loads-delay: 120ms;">
          <span class="loads-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600" style="--loads-delay: 150ms;">
            <i class="fa-solid fa-folder-tree text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Uploaded Files</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-slate-900">{{ $uploadedCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Stored source load files</p>
        </div>

        <div class="loads-card-motion loads-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--loads-delay: 150ms;">
          <span class="loads-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600" style="--loads-delay: 180ms;">
            <i class="fa-solid fa-circle-check text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Scanned Files</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-emerald-700">{{ $scannedCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Preview-ready load data</p>
        </div>

        <div class="loads-card-motion loads-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--loads-delay: 180ms;">
          <span class="loads-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600" style="--loads-delay: 210ms;">
            <i class="fa-solid fa-hourglass-half text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pending Scan</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-amber-600">{{ $pendingCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Files waiting for scan</p>
        </div>

        <div class="loads-card-motion loads-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--loads-delay: 210ms;">
          <span class="loads-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600" style="--loads-delay: 240ms;">
            <i class="fa-solid fa-eye text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">View Ready</p>
          <p class="mt-2 text-3xl font-black tracking-tight text-indigo-700">{{ $scannedCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Files marked scanned or processed</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.35fr)]">
        <section class="space-y-6">
          <div class="loads-reveal overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur" style="--loads-delay: 250ms;">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">
                  Upload Center
                </div>
                <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">Import loads file</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                  Static design preview only. This layout mirrors the payslip workflow for uploaded and scanned load files.
                </p>
              </div>
              <div class="hidden h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 sm:flex">
                <i class="fa-solid fa-file-arrow-up text-xl"></i>
              </div>
            </div>

            <div id="loads_message" class="hidden mt-5 rounded-2xl border px-4 py-3 text-sm"></div>
            @if (session('success'))
              <div class="mt-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
              </div>
            @endif
            @if ($errors->has('loads_file'))
              <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('loads_file') }}
              </div>
            @endif

            <form id="loads_upload_form" action="{{ route('admin.uploadLoadsFile') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
              @csrf
              <label for="loads_file" class="group relative flex cursor-pointer flex-col items-center justify-center overflow-hidden rounded-[1.5rem] border-2 border-dashed border-sky-200 bg-[linear-gradient(180deg,rgba(239,246,255,0.9),rgba(255,255,255,0.95))] px-6 py-10 text-center transition hover:border-sky-300 hover:bg-sky-50">
                <div class="absolute inset-x-6 top-0 h-px bg-gradient-to-r from-transparent via-sky-300 to-transparent"></div>
                <input id="loads_file" name="loads_file" type="file" accept=".xlsx,.xls,.csv" class="absolute inset-0 z-10 cursor-pointer opacity-0" />
                <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-sky-600 shadow-sm">
                  <i class="fa-solid fa-book-open-reader text-2xl"></i>
                </span>
                <p class="mt-5 text-base font-semibold text-slate-800">Browse loads file to upload</p>
                <p class="mt-2 text-sm text-slate-500">Accepted formats: `.xlsx`, `.xls`, and `.csv`.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">1. Upload</span>
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">2. Select</span>
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">3. Scan</span>
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">4. View</span>
                </div>
                <p id="selected_loads_name" class="mt-5 text-sm font-medium text-sky-700">No file selected</p>
              </label>

              <div class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Accepted</p>
                  <p class="mt-2 text-sm font-semibold text-slate-700">Excel and CSV</p>
                </div>
                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Scan Result</p>
                  <p class="mt-2 text-sm font-semibold text-slate-700">Preview-ready load records</p>
                </div>
                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Workflow</p>
                  <p class="mt-2 text-sm font-semibold text-slate-700">Design only, no backend</p>
                </div>
              </div>

              <div class="flex justify-end gap-3">
                <button id="upload_loads_btn" type="submit" disabled class="inline-flex items-center gap-2 rounded-full bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-300">
                  <i class="fa-solid fa-upload"></i>
                  Upload
                </button>
                <button id="scan_loads_btn" type="button" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                  <i class="fa-solid fa-barcode"></i>
                  Scan
                </button>
              </div>
            </form>
          </div>
        </section>

        <section class="loads-reveal overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur" style="--loads-delay: 290ms;">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                Processing Queue
              </div>
              <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">File status and preview access</h2>
              <p class="mt-2 text-sm leading-6 text-slate-500">
                Static uploaded and scanned cards styled after the payslip page for design review.
              </p>
            </div>
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-500">
              Uploads are live. Scan remains a design placeholder for now.
            </div>
          </div>

          <div class="mt-6 space-y-4">
            @forelse ($loadsFiles as $file)
              @php
                $isScanned = in_array(strtolower((string) ($file->status ?? '')), ['scanned', 'processed'], true);
                $statusLabel = $isScanned ? 'Scanned' : (trim((string) ($file->status ?? '')) !== '' ? trim((string) $file->status) : 'Uploaded');
                $progress = $isScanned ? 100 : 0;
              @endphp
              <div class="loads-file-item loads-row-motion group rounded-[1.5rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#f8fbff)] p-4" data-file-id="{{ $file->id }}">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center">
                  <div class="flex items-center gap-4">
                    <input type="radio" name="selected_loads_file" value="{{ $file->id }}" class="loads-file-radio h-4 w-4 cursor-pointer border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl {{ $isScanned ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }} shadow-sm">
                      <i class="fa-solid {{ $isScanned ? 'fa-file-lines' : 'fa-file-arrow-up' }} text-xl"></i>
                    </span>
                    <div>
                      <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-800">{{ $file->original_name }}</p>
                        <span class="loads-scan-status rounded-full {{ $isScanned ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-slate-200 bg-white text-slate-500' }} px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]">{{ $statusLabel }}</span>
                      </div>
                      <p class="mt-1 text-xs text-slate-500">{{ $isScanned ? 'Preview-ready load sheet.' : 'Stored file waiting for scan.' }}</p>
                    </div>
                  </div>

                  <div class="flex-1">
                    <div class="mb-2 flex items-center justify-between gap-2">
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Processing Progress</p>
                      <span class="loads-progress-text min-w-[40px] text-right text-xs font-semibold text-slate-700">{{ $progress }}%</span>
                    </div>
                    <div class="w-full rounded-full bg-slate-200 h-2.5">
                      <div class="loads-progress-bar h-2.5 rounded-full bg-gradient-to-r from-emerald-400 via-sky-500 to-indigo-500" style="width: {{ $progress }}%"></div>
                    </div>
                  </div>

                  <div class="min-w-[190px] text-left text-xs text-slate-500 xl:text-right">
                    <form method="POST" action="{{ route('admin.deleteLoadsFile', ['id' => $file->id]) }}" class="mb-2 flex justify-start xl:justify-end" onsubmit="return confirm('Remove this loads file?');">
                      @csrf
                      <button
                        type="submit"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700"
                        title="Remove file"
                        aria-label="Remove file"
                      >
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </form>
                    <span class="block">{{ optional($file->uploaded_at)->format('M d, Y') ?? '-' }}</span>
                    <span class="block">{{ optional($file->uploaded_at)->format('h:i A') ?? '-' }}</span>
                    @if ($isScanned)
                      <a href="#" class="loads-view-link mt-3 inline-flex w-full items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100 hover:text-sky-800">
                        <i class="fa-solid fa-eye mr-2"></i>
                        View
                      </a>
                    @else
                      <span class="loads-view-link mt-3 inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-400">
                        Awaiting Scan
                      </span>
                    @endif
                  </div>
                </div>
              </div>
            @empty
              <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50/80 p-8 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                  <i class="fa-solid fa-folder-open text-xl"></i>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-700">No uploaded load files yet.</p>
                <p class="mt-1 text-sm text-slate-500">Upload an Excel or CSV file to save it into the loads upload table.</p>
              </div>
            @endforelse
          </div>
        </section>
      </div>

      <section class="loads-reveal overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur" style="--loads-delay: 330ms;">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-700">
              Loads Summary
            </div>
            <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">Teaching load totals by employee</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">
              Summary of scanned loads grouped by employee name with subject count and unit totals.
            </p>
          </div>
          <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-500">
            {{ $loadsSummary->count() }} employee summary row(s)
          </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200">
          <table class="min-w-full border-collapse text-sm text-slate-800">
            <thead class="bg-slate-100">
              <tr>
                <th class="border border-slate-200 px-4 py-3 text-left font-semibold">Name</th>
                <th class="border border-slate-200 px-4 py-3 text-center font-semibold">Subjects</th>
                <th class="border border-slate-200 px-4 py-3 text-center font-semibold">Units</th>
                <th class="border border-slate-200 px-4 py-3 text-center font-semibold">Lecture Units</th>
                <th class="border border-slate-200 px-4 py-3 text-center font-semibold">Lab Units</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($loadsSummary as $summary)
                <tr class="odd:bg-white even:bg-slate-50">
                  <td class="border border-slate-200 px-4 py-3 font-medium text-slate-900">{{ $summary->employee_name }}</td>
                  <td class="border border-slate-200 px-4 py-3 text-center">{{ (int) ($summary->subject_count ?? 0) }}</td>
                  <td class="border border-slate-200 px-4 py-3 text-center">{{ number_format((float) ($summary->total_units ?? 0), 2) }}</td>
                  <td class="border border-slate-200 px-4 py-3 text-center">{{ number_format((float) ($summary->total_lec_units ?? 0), 2) }}</td>
                  <td class="border border-slate-200 px-4 py-3 text-center">{{ number_format((float) ($summary->total_lab_units ?? 0), 2) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="border border-slate-200 px-4 py-8 text-center text-slate-500">
                    No scanned loads summary available yet.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
</div>

<script>
  const initLoadsPageAnimation = () => {
    const page = document.getElementById('admin-loads-page');
    if (!page) return;

    const revealItems = Array.from(page.querySelectorAll('.loads-reveal'));
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
    document.addEventListener('DOMContentLoaded', initLoadsPageAnimation, { once: true });
  } else {
    initLoadsPageAnimation();
  }

  const loadsInput = document.getElementById('loads_file');
  const loadsName = document.getElementById('selected_loads_name');
  const uploadLoadsBtn = document.getElementById('upload_loads_btn');
  const scanLoadsBtn = document.getElementById('scan_loads_btn');
  const loadsUploadForm = document.getElementById('loads_upload_form');
  const loadsMessage = document.getElementById('loads_message');
  const scanLoadsUrlBase = @json(url('system/loads/update-status'));

  const showLoadsMessage = (text, type = 'success') => {
    if (!loadsMessage) return;
    loadsMessage.classList.remove(
      'hidden',
      'border-green-200',
      'bg-green-50',
      'text-green-700',
      'border-red-200',
      'bg-red-50',
      'text-red-700'
    );

    if (type === 'error') {
      loadsMessage.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
    } else {
      loadsMessage.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
    }

    loadsMessage.textContent = text;
  };

  if (loadsInput && loadsName && uploadLoadsBtn) {
    loadsInput.addEventListener('change', function () {
      const hasFile = this.files && this.files.length > 0;
      loadsName.textContent = hasFile ? this.files[0].name : 'No file selected';
      uploadLoadsBtn.disabled = !hasFile;
    });

    loadsUploadForm?.addEventListener('submit', function (event) {
      if (!loadsInput.files || !loadsInput.files.length) {
        event.preventDefault();
        return;
      }

      uploadLoadsBtn.disabled = true;
      uploadLoadsBtn.classList.add('opacity-60', 'cursor-not-allowed');
    });
  }

  if (scanLoadsBtn) {
    scanLoadsBtn.addEventListener('click', async function () {
      const selected = document.querySelector('input[name="selected_loads_file"]:checked');
      if (!selected) {
        showLoadsMessage('Please select an uploaded file first.', 'error');
        return;
      }

      const row = selected.closest('.loads-file-item');
      const fileId = selected.value;
      const status = row?.querySelector('.loads-scan-status');
      const progressText = row?.querySelector('.loads-progress-text');
      const progressBar = row?.querySelector('.loads-progress-bar');
      const viewLink = row?.querySelector('.loads-view-link');
      if (!row || !fileId || !status || !progressText || !progressBar) return;

      scanLoadsBtn.disabled = true;
      scanLoadsBtn.classList.add('opacity-60', 'cursor-not-allowed');

      try {
        status.textContent = 'Processing';
        status.className = 'loads-scan-status rounded-full border border-amber-200 bg-amber-50 text-amber-700 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]';
        progressText.textContent = '15%';
        progressBar.style.width = '15%';
        await new Promise((resolve) => setTimeout(resolve, 250));
        progressText.textContent = '48%';
        progressBar.style.width = '48%';
        await new Promise((resolve) => setTimeout(resolve, 280));
        progressText.textContent = '76%';
        progressBar.style.width = '76%';
        await new Promise((resolve) => setTimeout(resolve, 280));

        const response = await fetch(`${scanLoadsUrlBase}/${fileId}`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('#loads_upload_form input[name="_token"]')?.value || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ status: 'Scanned' }),
        });

        const payload = await response.json();
        if (!response.ok || !payload?.success) {
          throw new Error(payload?.message || 'Unable to scan the selected loads file.');
        }

        status.textContent = 'Scanned';
        status.className = 'loads-scan-status rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]';
        progressText.textContent = '100%';
        progressBar.style.width = '100%';
        if (viewLink) {
          viewLink.textContent = 'View';
          viewLink.className = 'loads-view-link mt-3 inline-flex w-full items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100 hover:text-sky-800';
          viewLink.innerHTML = '<i class="fa-solid fa-eye mr-2"></i>View';
        }
        showLoadsMessage('File scanned successfully.');
      } catch (error) {
        showLoadsMessage(error.message || 'Unable to scan the selected loads file.', 'error');
      } finally {
        scanLoadsBtn.disabled = false;
        scanLoadsBtn.classList.remove('opacity-60', 'cursor-not-allowed');
      }
    });
  }

  const loadsFileRadios = document.querySelectorAll('.loads-file-radio');
  const syncLoadsSelectionState = () => {
    document.querySelectorAll('.loads-file-item').forEach((item) => {
      item.classList.remove('loads-file-active');
    });

    const selected = document.querySelector('.loads-file-radio:checked');
    selected?.closest('.loads-file-item')?.classList.add('loads-file-active');
  };

  loadsFileRadios.forEach((radio) => {
    radio.addEventListener('change', syncLoadsSelectionState);
  });

  syncLoadsSelectionState();
</script>
</body>
</html>
