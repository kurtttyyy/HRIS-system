<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Payslip</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .payslip-file-active {
      border-color: rgb(125 211 252);
      background: linear-gradient(180deg, rgba(224, 242, 254, 0.85), rgba(255, 255, 255, 0.96));
      box-shadow: 0 18px 38px rgba(14, 165, 233, 0.12);
    }
    .payslip-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .payslip-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .payslip-reveal.is-visible {
      animation: payslip-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--payslip-delay, 0ms);
    }
    .payslip-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .payslip-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .payslip-icon-pop {
      animation: payslip-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--payslip-delay, 0ms);
    }
    .payslip-row-motion {
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .payslip-row-motion:hover {
      transform: translateX(4px);
      border-color: rgb(186 230 253);
      box-shadow: inset 3px 0 0 rgba(14, 165, 233, 0.55), 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    @keyframes payslip-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes payslip-pop-in {
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
      .payslip-reveal,
      .payslip-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .payslip-card-motion,
      .payslip-row-motion {
        transition: none;
      }
      .payslip-card-motion:hover,
      .payslip-row-motion:hover {
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
      $payslipFiles = $payslipFiles ?? collect();
      $payslipFileItems = $payslipFiles instanceof \Illuminate\Pagination\AbstractPaginator
        ? collect($payslipFiles->items())
        : collect($payslipFiles);
      $uploadedCount = isset($uploadedCount)
        ? (int) $uploadedCount
        : (method_exists($payslipFiles, 'total') ? (int) $payslipFiles->total() : $payslipFileItems->count());
      $scannedCount = isset($scannedCount)
        ? (int) $scannedCount
        : $payslipFileItems->filter(fn ($file) => in_array(strtolower((string) ($file->status ?? '')), ['scanned', 'processed']))->count();
      $pendingCount = max($uploadedCount - $scannedCount, 0);
      $latestUpload = $latestUpload
        ?? $payslipFileItems->sortByDesc(fn ($file) => optional($file->uploaded_at)?->timestamp ?? 0)->first();
    @endphp

    <div id="admin-payslip-page" class="p-4 md:p-8 pt-10 space-y-6">
      <section class="payslip-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] px-6 py-6 shadow-[0_24px_60px_rgba(3,19,29,0.34)] md:px-8" style="--payslip-delay: 0ms;">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="absolute -left-8 top-6 h-24 w-24 rounded-full bg-cyan-300/10 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-32 w-32 translate-x-10 -translate-y-8 rounded-full bg-emerald-300/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-50">
              <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
              Payroll Center
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">Payslip Operations</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85 md:text-base">
              Upload payroll files, scan them into preview-ready records, and move from raw spreadsheet to payslip review in one workspace.
            </p>
            <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-emerald-50/80">
              <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ now()->format('l, F j, Y') }}</span>
              <span id="payslip-header-uploaded-count" class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $uploadedCount }} uploaded file(s)</span>
              <span id="payslip-header-scanned-count" class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $scannedCount }} scanned</span>
            </div>
          </div>

          <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[420px]">
            <div class="payslip-card-motion payslip-reveal rounded-2xl border border-white/10 bg-white/8 px-4 py-4 shadow-sm backdrop-blur" style="--payslip-delay: 70ms;">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-50/70">Latest Upload</p>
              <p id="payslip-latest-upload-name" class="mt-2 text-sm font-semibold text-white">{{ $latestUpload?->original_name ?? 'No file yet' }}</p>
              <p id="payslip-latest-upload-date" class="mt-1 text-xs text-emerald-50/75">{{ optional($latestUpload?->uploaded_at)->format('M d, Y h:i A') ?? 'Waiting for first upload' }}</p>
            </div>
            <div class="payslip-card-motion payslip-reveal rounded-2xl border border-white/10 bg-white/8 px-4 py-4 shadow-sm backdrop-blur" style="--payslip-delay: 100ms;">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-50/70">Queue Status</p>
              <p id="payslip-header-pending-count" class="mt-2 text-sm font-semibold text-white">{{ $pendingCount }} pending scan</p>
              <p class="mt-1 text-xs text-emerald-50/75">Scan reads and saves file data for payslip view.</p>
            </div>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="payslip-card-motion payslip-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--payslip-delay: 120ms;">
          <span class="payslip-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600" style="--payslip-delay: 150ms;">
            <i class="fa-solid fa-folder-tree text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Uploaded Files</p>
          <p id="payslip-count-uploaded" class="mt-2 text-3xl font-black tracking-tight text-slate-900">{{ $uploadedCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Stored payroll source files</p>
        </div>

        <div class="payslip-card-motion payslip-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--payslip-delay: 150ms;">
          <span class="payslip-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600" style="--payslip-delay: 180ms;">
            <i class="fa-solid fa-circle-check text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Scanned Files</p>
          <p id="payslip-count-scanned" class="mt-2 text-3xl font-black tracking-tight text-emerald-700">{{ $scannedCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Preview-ready payslip data</p>
        </div>

        <div class="payslip-card-motion payslip-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--payslip-delay: 180ms;">
          <span class="payslip-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600" style="--payslip-delay: 210ms;">
            <i class="fa-solid fa-hourglass-half text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pending Scan</p>
          <p id="payslip-count-pending" class="mt-2 text-3xl font-black tracking-tight text-amber-600">{{ $pendingCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Files waiting for processing</p>
        </div>

        <div class="payslip-card-motion payslip-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--payslip-delay: 210ms;">
          <span class="payslip-icon-pop inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600" style="--payslip-delay: 240ms;">
            <i class="fa-solid fa-eye text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">View Ready</p>
          <p id="payslip-count-ready" class="mt-2 text-3xl font-black tracking-tight text-indigo-700">{{ $scannedCount }}</p>
          <p class="mt-1 text-sm text-slate-500">Files with available preview</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.35fr)]">
        <section class="space-y-6">
          <div class="payslip-reveal overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur" style="--payslip-delay: 250ms;">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">
                  Upload Center
                </div>
                <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">Import payslip file</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                  Upload payroll spreadsheets in `.xlsx` or `.csv`, then select one from the queue to scan and open its preview.
                </p>
              </div>
              <div class="hidden h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 sm:flex">
                <i class="fa-solid fa-file-arrow-up text-xl"></i>
              </div>
            </div>

            <div id="payslip_message" class="hidden mt-5 rounded-2xl border px-4 py-3 text-sm"></div>
            @if (session('success'))
              <div class="mt-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
              </div>
            @endif
            @if ($errors->has('payslip_file'))
              <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('payslip_file') }}
              </div>
            @endif

            <form id="payslip_upload_form" action="{{ route('admin.uploadPayslipFile') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
              @csrf
              <label for="payslip_file" class="group relative flex cursor-pointer flex-col items-center justify-center overflow-hidden rounded-[1.5rem] border-2 border-dashed border-sky-200 bg-[linear-gradient(180deg,rgba(239,246,255,0.9),rgba(255,255,255,0.95))] px-6 py-10 text-center transition hover:border-sky-300 hover:bg-sky-50">
                <div class="absolute inset-x-6 top-0 h-px bg-gradient-to-r from-transparent via-sky-300 to-transparent"></div>
                <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-sky-600 shadow-sm">
                  <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
                </span>
                <p class="mt-5 text-base font-semibold text-slate-800">Browse payslip file to upload</p>
                <p class="mt-2 text-sm text-slate-500">Accepted formats: `.xlsx` and `.csv`.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">1. Upload</span>
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">2. Select</span>
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">3. Scan</span>
                  <span class="rounded-full border border-slate-200 bg-white px-3 py-1">4. View</span>
                </div>
                <p id="selected_payslip_name" class="mt-5 text-sm font-medium text-sky-700">No file selected</p>
              </label>

              <input id="payslip_file" name="payslip_file" type="file" accept=".xlsx,.csv" class="hidden" />

              <div class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Accepted</p>
                  <p class="mt-2 text-sm font-semibold text-slate-700">Excel and CSV</p>
                </div>
                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Scan Result</p>
                  <p class="mt-2 text-sm font-semibold text-slate-700">Preview-ready records</p>
                </div>
                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Workflow</p>
                  <p class="mt-2 text-sm font-semibold text-slate-700">Stored before processing</p>
                </div>
              </div>

              <div class="flex justify-end gap-3">
                <button id="upload_payslip_btn" type="submit" disabled class="inline-flex items-center gap-2 rounded-full bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-300">
                  <i class="fa-solid fa-upload"></i>
                  Upload
                </button>
                <button id="scan_payslip_btn" type="button" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                  <i class="fa-solid fa-barcode"></i>
                  Scan
                </button>
              </div>
            </form>
          </div>
        </section>

        <section class="payslip-reveal overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur" style="--payslip-delay: 290ms;">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                Processing Queue
              </div>
              <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">File status and preview access</h2>
              <p class="mt-2 text-sm leading-6 text-slate-500">
                Select a file to scan, monitor processing status, and open the payslip preview once scanning completes.
              </p>
            </div>
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-500">
              Scan currently reads the Excel/CSV for preview and saves the file data for payslip view.
            </div>
          </div>

          <div id="payslip_file_list" class="mt-6 space-y-4">
            @forelse ($payslipFileItems as $file)
              @php
                $isScanned = strcasecmp((string) ($file->status ?? ''), 'Scanned') === 0
                  || strcasecmp((string) ($file->status ?? ''), 'Processed') === 0;
                $statusLabel = $isScanned
                  ? 'Scanned'
                  : (trim((string) ($file->status ?? '')) !== '' ? trim((string) $file->status) : 'Uploaded');
                $progress = $isScanned ? 100 : 0;
              @endphp
              <div class="payslip-file-item payslip-row-motion group rounded-[1.5rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#f8fbff)] p-4" data-file-id="{{ $file->id }}">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center">
                  <div class="flex items-center gap-4">
                    <input type="radio" name="selected_payslip_file" value="{{ $file->id }}" class="payslip-file-radio h-4 w-4 cursor-pointer border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 shadow-sm">
                      <i class="fa-solid fa-file-excel text-xl"></i>
                    </span>

                    <div>
                      <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-800">{{ $file->original_name }}</p>
                        <span class="payslip-scan-status rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $statusLabel }}</span>
                      </div>
                      <p class="mt-1 text-xs text-slate-500">Stored file ready for payroll scan and preview generation.</p>
                    </div>
                  </div>

                  <div class="flex-1">
                    <div class="mb-2 flex items-center justify-between gap-2">
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Processing Progress</p>
                      <span class="payslip-progress-text min-w-[40px] text-right text-xs font-semibold text-slate-700">{{ $progress }}%</span>
                    </div>
                    <div class="w-full rounded-full bg-slate-200 h-2.5">
                      <div class="payslip-progress-bar h-2.5 rounded-full bg-gradient-to-r from-emerald-400 via-sky-500 to-indigo-500 transition-all duration-500" style="width: {{ $progress }}%"></div>
                    </div>
                  </div>

                  <div class="min-w-[160px] text-left text-xs text-slate-500 xl:text-right">
                    <span class="block">{{ optional($file->uploaded_at)->format('M d, Y') ?? '-' }}</span>
                    <span class="block">{{ optional($file->uploaded_at)->format('h:i A') ?? '-' }}</span>
                    <a href="{{ route('admin.adminPaySlipView', ['upload_id' => $file->id]) }}" class="payslip-view-link {{ $isScanned ? '' : 'hidden' }} mt-3 inline-flex w-full items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100 hover:text-sky-800">
                      <i class="fa-solid fa-eye mr-2"></i>
                      View
                    </a>
                  </div>
                </div>
              </div>
            @empty
              <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50/80 p-8 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                  <i class="fa-solid fa-folder-open text-xl"></i>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-700">No uploaded files yet.</p>
                <p class="mt-1 text-sm text-slate-500">Upload a payslip source file to start the payroll processing flow.</p>
              </div>
            @endforelse
          </div>
          @if ($payslipFiles instanceof \Illuminate\Pagination\AbstractPaginator && $payslipFiles->hasPages())
            <div class="mt-5">
              {{ $payslipFiles->links() }}
            </div>
          @endif
        </section>
      </div>
    </div>
  </main>
</div>

<script>
  const initPayslipPageAnimation = () => {
    const page = document.getElementById('admin-payslip-page');
    if (!page) return;

    const revealItems = Array.from(page.querySelectorAll('.payslip-reveal'));
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
    document.addEventListener('DOMContentLoaded', initPayslipPageAnimation, { once: true });
  } else {
    initPayslipPageAnimation();
  }

  const sidebar = document.querySelector('aside');
  const main = document.querySelector('main');
  const payslipInput = document.getElementById('payslip_file');
  const payslipName = document.getElementById('selected_payslip_name');
  const uploadPayslipBtn = document.getElementById('upload_payslip_btn');
  const scanPayslipBtn = document.getElementById('scan_payslip_btn');
  const payslipFileList = document.getElementById('payslip_file_list');
  const payslipMessage = document.getElementById('payslip_message');
  const payslipUploadForm = document.getElementById('payslip_upload_form');
  const payslipScanUrlBase = @json(url('admin/payslip/update-status'));
  let scanInProgress = false;

  const showPayslipMessage = (text, type = 'success') => {
    if (!payslipMessage) return;
    payslipMessage.classList.remove(
      'hidden',
      'border-green-200',
      'bg-green-50',
      'text-green-700',
      'border-red-200',
      'bg-red-50',
      'text-red-700'
    );

    if (type === 'error') {
      payslipMessage.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
    } else {
      payslipMessage.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
    }

    payslipMessage.textContent = text;
  };

  const refreshAdminPayslipContent = async () => {
    const refreshUrl = new URL(window.location.href);
    refreshUrl.searchParams.delete('page');
    const response = await fetch(refreshUrl.toString(), {
      headers: {
        'Accept': 'text/html',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (!response.ok) {
      throw new Error('The file was uploaded, but the queue could not be refreshed.');
    }

    const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
    const nextFileList = documentCopy.getElementById('payslip_file_list');
    if (!nextFileList || !payslipFileList) {
      throw new Error('The file was uploaded, but the queue could not be refreshed.');
    }

    [
      'payslip-header-uploaded-count',
      'payslip-header-scanned-count',
      'payslip-latest-upload-name',
      'payslip-latest-upload-date',
      'payslip-header-pending-count',
      'payslip-count-uploaded',
      'payslip-count-scanned',
      'payslip-count-pending',
      'payslip-count-ready',
    ].forEach((id) => {
      const currentElement = document.getElementById(id);
      const nextElement = documentCopy.getElementById(id);
      if (currentElement && nextElement) {
        currentElement.textContent = nextElement.textContent;
      }
    });

    payslipFileList.innerHTML = nextFileList.innerHTML;
  };

  if (payslipInput && payslipName && uploadPayslipBtn && scanPayslipBtn) {
    payslipInput.addEventListener('change', function () {
      const hasFile = this.files && this.files.length > 0;
      payslipName.textContent = hasFile ? this.files[0].name : 'No file selected';
      uploadPayslipBtn.disabled = !hasFile;
    });

    payslipUploadForm?.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!payslipInput.files || !payslipInput.files.length) {
        showPayslipMessage('Please choose a file first.', 'error');
        return;
      }

      const originalButtonHtml = uploadPayslipBtn.innerHTML;
      uploadPayslipBtn.disabled = true;
      uploadPayslipBtn.classList.add('opacity-60', 'cursor-not-allowed');
      uploadPayslipBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';

      try {
        const response = await fetch(payslipUploadForm.action, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new FormData(payslipUploadForm),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
          const validationMessage = payload.errors
            ? Object.values(payload.errors).flat()[0]
            : null;
          throw new Error(validationMessage || payload.message || 'Unable to upload the payslip file.');
        }

        payslipUploadForm.reset();
        payslipName.textContent = 'No file selected';
        await refreshAdminPayslipContent();
        showPayslipMessage(payload.message || 'Payslip file uploaded successfully.');
      } catch (error) {
        showPayslipMessage(error.message || 'Unable to upload the payslip file.', 'error');
      } finally {
        uploadPayslipBtn.disabled = !(payslipInput.files && payslipInput.files.length);
        uploadPayslipBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        uploadPayslipBtn.innerHTML = originalButtonHtml;
      }
    });

    scanPayslipBtn.addEventListener('click', function () {
      if (scanInProgress) return;
      const selected = document.querySelector('input[name="selected_payslip_file"]:checked');
      if (!selected) {
        showPayslipMessage('Please select an uploaded file first.', 'error');
        return;
      }

      const row = selected.closest('.payslip-file-item');
      const fileId = selected.value;
      const bar = row?.querySelector('.payslip-progress-bar');
      const status = row?.querySelector('.payslip-scan-status');
      const progressText = row?.querySelector('.payslip-progress-text');
      const viewLink = row?.querySelector('.payslip-view-link');
      if (!bar || !status || !progressText || !fileId) return;
      const previousStatusText = status.textContent;
      const previousProgressText = progressText.textContent;
      const previousBarWidth = bar.style.width;

      scanInProgress = true;
      scanPayslipBtn.disabled = true;
      uploadPayslipBtn.disabled = true;
      scanPayslipBtn.classList.add('opacity-60', 'cursor-not-allowed');
      uploadPayslipBtn.classList.add('opacity-60', 'cursor-not-allowed');
      scanPayslipBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Scanning...';
      status.textContent = 'Scanning';
      progressText.textContent = '0%';

      const durationMs = 2200;
      const maxBeforeComplete = 92;
      const startTime = performance.now();
      const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

      const animate = (now) => {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / durationMs, 1);
        const eased = easeOutCubic(progress);
        const width = Math.round(eased * maxBeforeComplete);

        bar.style.width = `${width}%`;
        progressText.textContent = `${width}%`;

        if (progress < 1) {
          requestAnimationFrame(animate);
          return;
        }

        fetch(`${payslipScanUrlBase}/${fileId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('#payslip_upload_form input[name="_token"]')?.value || '',
          },
          body: JSON.stringify({ status: 'Scanned' }),
        })
          .then((response) => response.json())
          .then((payload) => {
            if (!payload?.success) {
              throw new Error(payload?.message || 'Scan update failed.');
            }

            bar.style.width = '100%';
            progressText.textContent = '100%';
            status.textContent = 'Scanned';
            scanPayslipBtn.innerHTML = '<i class="fa-solid fa-check mr-2"></i>Scanned';
            scanPayslipBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            scanPayslipBtn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
            if (viewLink) {
              viewLink.classList.remove('hidden');
            }
            const rows = Number(payload?.processed_rows || 0);
            showPayslipMessage(`File scanned and saved successfully. Matched rows: ${rows}.`);
          })
          .catch((error) => {
            console.error(error);
            status.textContent = previousStatusText;
            progressText.textContent = previousProgressText;
            bar.style.width = previousBarWidth;
            showPayslipMessage('Failed to scan and save file data.', 'error');
          })
          .finally(() => {
            scanPayslipBtn.disabled = false;
            uploadPayslipBtn.disabled = false;
            scanPayslipBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            uploadPayslipBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            scanInProgress = false;
          });
      };

      requestAnimationFrame(animate);
    });
  }

  const setActivePayslipItem = (activeItem) => {
    document.querySelectorAll('.payslip-file-item').forEach((row) => row.classList.remove('payslip-file-active'));
    if (activeItem) {
      activeItem.classList.add('payslip-file-active');
    }
  };

  payslipFileList?.addEventListener('click', function (event) {
    if (event.target.closest('a')) return;

    const item = event.target.closest('.payslip-file-item');
    if (!item) return;

    const radio = item.querySelector('.payslip-file-radio');
    if (radio) {
      radio.checked = true;
    }

    setActivePayslipItem(item);
  });

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
</body>
</html>
