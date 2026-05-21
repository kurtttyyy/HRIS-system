<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Resignation Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .resignation-reveal {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.28s ease, transform 0.28s ease;
      will-change: opacity, transform;
    }
    .resignation-reveal.reveal-from-top {
      transform: translateY(-18px);
    }
    .resignation-reveal.is-visible {
      animation: resignation-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
      animation-delay: var(--resignation-delay, 0ms);
    }
    .resignation-card-motion {
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
    }
    .resignation-card-motion:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .resignation-icon-pop {
      animation: resignation-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
      animation-delay: var(--resignation-delay, 0ms);
    }
    .resignation-row-motion {
      transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .resignation-row-motion:hover {
      transform: translateX(4px);
      box-shadow: inset 3px 0 0 rgba(16, 185, 129, 0.55);
    }
    @keyframes resignation-fade-up {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes resignation-pop-in {
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
      .resignation-reveal,
      .resignation-icon-pop {
        animation: none;
        opacity: 1;
        transform: none;
      }
      .resignation-card-motion,
      .resignation-row-motion {
        transition: none;
      }
      .resignation-card-motion:hover,
      .resignation-row-motion:hover {
        transform: none;
      }
    }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#f1f5f9_45%,#eefbf6_100%)] text-slate-800">
<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    <div id="admin-resignation-page" class="space-y-6 p-4 pt-10 md:p-8">
      <section class="resignation-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] px-6 py-7 text-white shadow-[0_24px_60px_rgba(3,19,29,0.34)] md:px-8" style="--resignation-delay: 0ms;">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="absolute -right-12 -top-16 h-44 w-44 rounded-full bg-cyan-300/10 blur-2xl"></div>
        <div class="absolute bottom-0 right-24 h-28 w-28 rounded-full bg-emerald-300/20 blur-2xl"></div>

        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-50">
              Exit Workflow
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-tight md:text-4xl">Resignation management with better visibility, faster decisions, and cleaner records.</h1>
            <p class="mt-3 max-w-2xl text-sm text-slate-200 md:text-base">
              Review pending requests, update resignation outcomes, and keep employee exit tracking organized in one admin workspace.
            </p>
          </div>

            <div class="grid gap-3 sm:grid-cols-2">
            <div class="resignation-card-motion resignation-reveal rounded-[1.5rem] border border-white/10 bg-white/8 px-5 py-4 backdrop-blur" style="--resignation-delay: 70ms;">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-50/75">Today</p>
              <p class="mt-2 text-lg font-bold">{{ now()->format('F j, Y') }}</p>
              <p class="text-sm text-emerald-50/80">{{ now()->format('l') }}</p>
            </div>
            <div class="resignation-card-motion resignation-reveal rounded-[1.5rem] border border-white/10 bg-white/8 px-5 py-4 backdrop-blur" style="--resignation-delay: 100ms;">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-50/75">Pending Queue</p>
              <p class="mt-2 text-2xl font-black">{{ $pendingResignations->count() }}</p>
              <p class="text-sm text-emerald-50/80">Requests waiting for review</p>
            </div>
          </div>
        </div>
      </section>

      @if (session('success'))
        <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">
          {{ session('success') }}
        </div>
      @endif
      @if (session('error'))
        <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800 shadow-sm">
          {{ session('error') }}
        </div>
      @endif
      @if ($errors->any())
        <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800 shadow-sm">
          {{ $errors->first() }}
        </div>
      @endif

      <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
        <article class="resignation-card-motion resignation-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--resignation-delay: 120ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pending</p>
              <p id="resignation-count-pending" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ (int) ($statusCounts['Pending'] ?? 0) }}</p>
              <p class="mt-1 text-sm text-slate-500">Requests awaiting approval decision</p>
            </div>
            <div class="text-right">
              <div class="resignation-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600" style="--resignation-delay: 150ms;">
                <i class="fa-regular fa-hourglass-half"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Review</span>
            </div>
          </div>
        </article>

        <article class="resignation-card-motion resignation-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--resignation-delay: 150ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Approved</p>
              <p id="resignation-count-approved" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ (int) ($statusCounts['Approved'] ?? 0) }}</p>
              <p class="mt-1 text-sm text-slate-500">Confirmed resignations in process</p>
            </div>
            <div class="text-right">
              <div class="resignation-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600" style="--resignation-delay: 180ms;">
                <i class="fa-solid fa-thumbs-up"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Approved</span>
            </div>
          </div>
        </article>

        <article class="resignation-card-motion resignation-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--resignation-delay: 180ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Rejected</p>
              <p id="resignation-count-rejected" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ (int) ($statusCounts['Rejected'] ?? 0) }}</p>
              <p class="mt-1 text-sm text-slate-500">Requests declined after review</p>
            </div>
            <div class="text-right">
              <div class="resignation-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600" style="--resignation-delay: 210ms;">
                <i class="fa-solid fa-ban"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Declined</span>
            </div>
          </div>
        </article>

        <article class="resignation-card-motion resignation-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--resignation-delay: 210ms;">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Cancelled</p>
              <p id="resignation-count-cancelled" class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ (int) ($statusCounts['Cancelled'] ?? 0) }}</p>
              <p class="mt-1 text-sm text-slate-500">Requests withdrawn or voided</p>
            </div>
            <div class="text-right">
              <div class="resignation-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200 text-slate-700" style="--resignation-delay: 240ms;">
                <i class="fa-solid fa-rotate-left"></i>
              </div>
              <span class="mt-3 inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">Closed</span>
            </div>
          </div>
        </article>
      </section>

      <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="resignation-reveal rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur xl:col-span-1" style="--resignation-delay: 250ms;">
          <div class="flex items-center justify-between gap-3">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700">
                Attention Queue
              </div>
              <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">Pending Requests</h2>
              <p class="mt-1 text-sm text-slate-500">Review active resignation submissions and act quickly on approvals or rejections.</p>
            </div>
            <span id="pending-requests-badge" class="inline-flex h-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
              {{ $pendingResignations->count() }}
            </span>
          </div>

          <div id="pending-requests-list" class="mt-6 space-y-4 max-h-[760px] overflow-y-auto pr-1">
            @forelse ($pendingResignations as $pending)
              @php
                $initials = collect(explode(' ', trim((string) $pending->employee_name)))
                  ->filter()
                  ->take(2)
                  ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                  ->implode('');
              @endphp
              <article class="resignation-card-motion rounded-[1.75rem] border border-amber-100 bg-[linear-gradient(180deg,rgba(255,251,235,0.95),rgba(255,255,255,0.98))] p-5 shadow-[0_12px_30px_rgba(245,158,11,0.08)]" data-pending-item="{{ $pending->id }}">
                <div class="flex items-start gap-4">
                  <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sm font-bold text-white">
                    {{ $initials !== '' ? $initials : 'NA' }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="text-lg font-black tracking-tight text-slate-900">{{ $pending->employee_name }}</h3>
                      <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700">
                        Pending
                      </span>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                      <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
                        <i class="fa-regular fa-id-badge text-slate-400"></i>
                        {{ $pending->employee_id ?: '-' }}
                      </span>
                      <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
                        <i class="fa-solid fa-building text-slate-400"></i>
                        {{ $pending->department ?: 'N/A' }}
                      </span>
                    </div>

                    <div class="mt-3 rounded-2xl bg-white/90 px-4 py-3 text-sm text-slate-600">
                      <div class="flex flex-wrap gap-x-4 gap-y-2">
                        <span><span class="font-semibold text-slate-700">Submitted:</span> {{ optional($pending->submitted_at)->format('M d, Y') ?? '-' }}</span>
                        <span><span class="font-semibold text-slate-700">Effective:</span> {{ optional($pending->effective_date)->format('M d, Y') ?? '-' }}</span>
                      </div>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $pending->reason ?: 'No reason provided.' }}</p>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                      <form method="POST" action="{{ route('admin.updateResignationStatus', $pending->id) }}" class="js-pending-action-form">
                        @csrf
                        <input type="hidden" name="status" value="Approved">
                        <input type="hidden" name="admin_note" value="">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                          <i class="fa-solid fa-check text-xs"></i>
                          Approve
                        </button>
                      </form>
                      <form method="POST" action="{{ route('admin.updateResignationStatus', $pending->id) }}" class="js-pending-action-form">
                        @csrf
                        <input type="hidden" name="status" value="Rejected">
                        <input type="hidden" name="admin_note" value="">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                          <i class="fa-solid fa-xmark text-xs"></i>
                          Reject
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </article>
            @empty
              <div id="pending-empty-state" class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white/70 p-8 text-center text-sm text-slate-500">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                  <i class="fa-regular fa-folder-open text-lg"></i>
                </div>
                <p class="mt-4 font-medium text-slate-700">No pending resignation requests.</p>
              </div>
            @endforelse
          </div>
        </div>

        <div class="resignation-reveal rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur xl:col-span-2" style="--resignation-delay: 290ms;">
          <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">
                Resignation Records
              </div>
              <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">Employee exit records</h2>
              <p class="mt-1 text-sm text-slate-500">Search, filter, and update resignation outcomes without leaving the dashboard.</p>
            </div>

            <form method="GET" action="{{ route('admin.adminResignations') }}" class="grid gap-3 sm:grid-cols-3 xl:min-w-[720px]">
              <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                <input
                  type="text"
                  name="search"
                  value="{{ $search }}"
                  placeholder="Search employee, ID, dept..."
                  class="w-full bg-transparent text-slate-700 outline-none"
                >
              </label>
              <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <i class="fa-solid fa-filter text-slate-400"></i>
                <select name="status" class="w-full bg-transparent text-slate-700 outline-none">
                  @foreach (['All', 'Pending', 'Approved', 'Completed', 'Rejected', 'Cancelled'] as $statusOption)
                    <option value="{{ $statusOption }}" {{ strcasecmp((string) $selectedStatus, $statusOption) === 0 ? 'selected' : '' }}>
                      {{ $statusOption }}
                    </option>
                  @endforeach
                </select>
              </label>
              <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                <i class="fa-solid fa-sliders"></i>
                Filter
              </button>
            </form>
          </div>

          <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Records Shown</p>
              <p class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ $resignations->count() }}</p>
            </div>
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Filter Status</p>
              <p class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ $selectedStatus ?: 'All' }}</p>
            </div>
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Search Term</p>
              <p class="mt-2 truncate text-2xl font-black tracking-tight text-slate-900">{{ $search ?: 'None' }}</p>
            </div>
          </div>

          <div class="mt-6 overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
              <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <tr>
                  <th class="px-5 py-4 font-semibold">Employee</th>
                  <th class="px-5 py-4 font-semibold">Submitted</th>
                  <th class="px-5 py-4 font-semibold">Effective</th>
                  <th class="px-5 py-4 font-semibold">Status</th>
                  <th class="px-5 py-4 font-semibold">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                @forelse ($resignations as $row)
                  @php
                    $statusText = trim((string) ($row->status ?? 'Pending'));
                    $statusClass = match (strtolower($statusText)) {
                      'approved' => 'bg-sky-100 text-sky-700',
                      'completed' => 'bg-emerald-100 text-emerald-700',
                      'rejected' => 'bg-rose-100 text-rose-700',
                      'cancelled' => 'bg-slate-200 text-slate-700',
                      default => 'bg-amber-100 text-amber-700',
                    };
                  @endphp
                  <tr class="resignation-row-motion transition hover:bg-slate-50/80">
                    <td class="px-5 py-4">
                      <p class="font-semibold text-slate-800">{{ $row->employee_name }}</p>
                      <p class="mt-1 text-xs text-slate-500">
                        {{ $row->employee_id ?: '-' }} | {{ $row->department ?: 'N/A' }} | {{ $row->position ?: 'N/A' }}
                      </p>
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ optional($row->submitted_at)->format('M d, Y') ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ optional($row->effective_date)->format('M d, Y') ?? '-' }}</td>
                    <td class="px-5 py-4">
                      <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                    <td class="px-5 py-4">
                      <form method="POST" action="{{ route('admin.updateResignationStatus', $row->id) }}" class="grid gap-2 xl:grid-cols-[120px_minmax(0,1fr)_auto] xl:items-center">
                        @csrf
                        <select name="status" class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none">
                          @foreach (['Pending', 'Approved', 'Completed', 'Rejected', 'Cancelled'] as $option)
                            <option value="{{ $option }}" {{ strcasecmp($statusText, $option) === 0 ? 'selected' : '' }}>{{ $option }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="admin_note" value="{{ $row->admin_note }}" placeholder="Add admin note" class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 outline-none">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                          <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                          Update
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fa-regular fa-folder-open text-lg"></i>
                      </div>
                      <p class="mt-4 font-medium text-slate-700">No resignation records found.</p>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </main>
</div>

<script>
  const initResignationPageAnimation = () => {
    const page = document.getElementById('admin-resignation-page');
    if (!page) return;

    const revealItems = Array.from(page.querySelectorAll('.resignation-reveal'));
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
    document.addEventListener('DOMContentLoaded', initResignationPageAnimation, { once: true });
  } else {
    initResignationPageAnimation();
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

  const pendingActionForms = Array.from(document.querySelectorAll('.js-pending-action-form'));
  const pendingBadge = document.getElementById('pending-requests-badge');
  const pendingList = document.getElementById('pending-requests-list');
  const pendingCardCount = document.getElementById('resignation-count-pending');
  const approvedCardCount = document.getElementById('resignation-count-approved');
  const rejectedCardCount = document.getElementById('resignation-count-rejected');
  const cancelledCardCount = document.getElementById('resignation-count-cancelled');

  function applyStatusCounts(counts) {
    if (!counts || typeof counts !== 'object') return;
    if (pendingCardCount && counts.Pending !== undefined) pendingCardCount.textContent = String(counts.Pending);
    if (approvedCardCount && counts.Approved !== undefined) approvedCardCount.textContent = String(counts.Approved);
    if (rejectedCardCount && counts.Rejected !== undefined) rejectedCardCount.textContent = String(counts.Rejected);
    if (cancelledCardCount && counts.Cancelled !== undefined) cancelledCardCount.textContent = String(counts.Cancelled);
    if (pendingBadge && counts.Pending !== undefined) pendingBadge.textContent = String(counts.Pending);
  }

  function ensurePendingEmptyState() {
    if (!pendingList) return;
    const pendingItems = pendingList.querySelectorAll('[data-pending-item]');
    let emptyState = document.getElementById('pending-empty-state');
    if (pendingItems.length > 0) {
      if (emptyState) emptyState.remove();
      return;
    }

    if (!emptyState) {
      emptyState = document.createElement('div');
      emptyState.id = 'pending-empty-state';
      emptyState.className = 'rounded-[1.5rem] border border-dashed border-slate-300 bg-white/70 p-8 text-center text-sm text-slate-500';
      emptyState.innerHTML = '<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i class="fa-regular fa-folder-open text-lg"></i></div><p class="mt-4 font-medium text-slate-700">No pending resignation requests.</p>';
      pendingList.appendChild(emptyState);
    }
  }

  pendingActionForms.forEach((form) => {
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: new FormData(form),
        });

        if (!response.ok) {
          throw new Error('Failed to update resignation status.');
        }

        const payload = await response.json();
        applyStatusCounts(payload.statusCounts || null);

        const pendingItem = form.closest('[data-pending-item]');
        if (pendingItem) {
          pendingItem.remove();
          ensurePendingEmptyState();
        }
      } catch (error) {
        window.location.reload();
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  });
</script>
</body>
</html>
