<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - HR Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
        body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
        main { transition: margin-left 0.3s ease; }
        aside ~ main { margin-left: 16rem; }
        .position-reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.28s ease, transform 0.28s ease;
            will-change: opacity, transform;
        }
        .position-reveal.reveal-from-top {
            transform: translateY(-18px);
        }
        .position-reveal.is-visible {
            animation: position-fade-up 0.42s cubic-bezier(0.22, 0.9, 0.2, 1) forwards;
            animation-delay: var(--position-delay, 0ms);
        }
        .position-card-motion {
            transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease, background-color 0.24s ease;
        }
        .position-card-motion:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }
        .position-icon-pop {
            animation: position-pop-in 0.65s cubic-bezier(0.22, 0.9, 0.2, 1) both;
            animation-delay: var(--position-delay, 0ms);
        }
        @keyframes position-fade-up {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes position-pop-in {
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
            .position-reveal,
            .position-icon-pop {
                animation: none;
                opacity: 1;
                transform: none;
            }
            .position-card-motion {
                transition: none;
            }
            .position-card-motion:hover {
                transform: none;
            }
        }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#f1f5f9_45%,#eefbf6_100%)]">

@php
    $departmentOptions = collect($openPosition ?? [])
        ->map(fn ($open) => trim((string) ($open->department ?? '')))
        ->filter(fn ($value) => $value !== '')
        ->unique()
        ->sort()
        ->values();

    $employmentOptions = collect($openPosition ?? [])
        ->map(fn ($open) => trim((string) ($open->employment ?? '')))
        ->filter(fn ($value) => $value !== '')
        ->unique()
        ->sort()
        ->values();

    $jobTypeOptions = collect($openPosition ?? [])
        ->map(fn ($open) => trim((string) ($open->job_type ?? '')))
        ->filter(fn ($value) => $value !== '')
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="flex min-h-screen">
    @include('components.adminSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        @include('components.adminHeader.positionHeader')

        <div id="admin-position-page" class="space-y-6 p-4 pt-20 md:p-8">
            @if (session('error'))
                <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium text-amber-800 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                @php
                    $activePositionCount = collect($openPosition ?? [])->whereNull('deleted_at')->count();
                    $closedPositionCount = collect($openPosition ?? [])->filter(fn ($position) => !is_null($position->deleted_at))->count();
                @endphp
                <div role="button" tabindex="0" data-position-dashboard-filter="active" class="position-stat-card position-card-motion position-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-[0_24px_55px_rgba(15,23,42,0.1)] focus:outline-none focus:ring-2 focus:ring-sky-300" style="--position-delay: 30ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Open Positions</p>
                            <p class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $activePositionCount }}</p>
                            <p class="mt-1 text-sm text-slate-500">Roles currently available for applicants</p>
                        </div>
                        <div class="text-right">
                            <div class="position-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600" style="--position-delay: 70ms;">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <span class="mt-3 inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Active</span>
                        </div>
                    </div>
                </div>

                <div class="position-stat-card position-card-motion position-reveal rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur" style="--position-delay: 60ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Total Views</p>
                            <p class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $logs }}</p>
                            <p class="mt-1 text-sm text-slate-500">Audience engagement across posted roles</p>
                        </div>
                        <div class="text-right">
                            <div class="position-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600" style="--position-delay: 100ms;">
                                <i class="fa-regular fa-eye"></i>
                            </div>
                            <span class="mt-3 inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">+24%</span>
                        </div>
                    </div>
                </div>

                <div role="button" tabindex="0" data-position-dashboard-filter="applications" class="position-stat-card position-card-motion position-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-[0_24px_55px_rgba(15,23,42,0.1)] focus:outline-none focus:ring-2 focus:ring-emerald-300" style="--position-delay: 90ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">New Applications</p>
                            <p class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $applicantCounts }}</p>
                            <p class="mt-1 text-sm text-slate-500">Applicants flowing into the hiring board</p>
                        </div>
                        <div class="text-right">
                            <div class="position-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600" style="--position-delay: 130ms;">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <span class="mt-3 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">This Week</span>
                        </div>
                    </div>
                </div>

                <div role="button" tabindex="0" data-position-dashboard-filter="closed" class="position-stat-card position-card-motion position-reveal cursor-pointer rounded-[1.75rem] border border-white/80 bg-white/90 p-5 text-left shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-[0_24px_55px_rgba(15,23,42,0.1)] focus:outline-none focus:ring-2 focus:ring-amber-300" style="--position-delay: 120ms;">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Closed Positions</p>
                            <p class="mt-3 text-4xl font-black tracking-tight text-slate-900">{{ $closedPositionCount }}</p>
                            <p class="mt-1 text-sm text-slate-500">Positions removed from the applicant-facing board</p>
                        </div>
                        <div class="text-right">
                            <div class="position-icon-pop flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600" style="--position-delay: 160ms;">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                            <span class="mt-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Archived</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="position-reveal rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur" style="--position-delay: 170ms;">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                            Role Filters
                        </div>
                        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">Position Board</h2>
                        <p class="mt-1 text-sm text-slate-500">Filter by department, employment setup, or job type to focus the hiring board.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[760px] xl:grid-cols-4">
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <i class="fa-solid fa-building text-slate-400"></i>
                            <select id="departmentFilter" class="w-full bg-transparent text-slate-700 outline-none">
                                <option value="">All Departments</option>
                                @foreach ($departmentOptions as $departmentOption)
                                    <option value="{{ strtolower($departmentOption) }}">{{ $departmentOption }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <i class="fa-solid fa-briefcase text-slate-400"></i>
                            <select id="employmentFilter" class="w-full bg-transparent text-slate-700 outline-none">
                                <option value="">All Employment</option>
                                @foreach ($employmentOptions as $employmentOption)
                                    <option value="{{ strtolower($employmentOption) }}">{{ $employmentOption }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <i class="fa-solid fa-layer-group text-slate-400"></i>
                            <select id="jobTypeFilter" class="w-full bg-transparent text-slate-700 outline-none">
                                <option value="">All Job Types</option>
                                @foreach ($jobTypeOptions as $jobTypeOption)
                                    <option value="{{ strtolower($jobTypeOption) }}">{{ $jobTypeOption }}</option>
                                @endforeach
                            </select>
                        </label>

                        <button id="resetPositionFilters" type="button" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div id="positionEmptyState" class="hidden rounded-[2rem] border border-dashed border-slate-300 bg-white/70 p-10 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="fa-solid fa-briefcase text-xl"></i>
                </div>
                <h3 class="mt-4 text-xl font-bold text-slate-900">No positions matched</h3>
                <p class="mt-2 text-sm text-slate-500">Try adjusting your search or filter selections to see more roles.</p>
            </div>

            <div id="positionCardsGrid" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                @foreach ($openPosition as $open)
                    @php
                        $lines = preg_split("/\r\n|\n|\r/", $open->job_description ?? '');
                        $descriptionPreview = collect(array_slice($lines, 0, 3))
                            ->map(fn ($line) => \Illuminate\Support\Str::limit(trim(preg_replace('/^[\s\-\*\x{2022}\x{2023}\x{25E6}\x{2043}]+/u', '', (string) $line)), 150, '...'))
                            ->filter(fn ($line) => trim((string) $line) !== '')
                            ->values();
                    @endphp

                    <article
                        class="position-card position-card-motion position-reveal group relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/95 p-6 shadow-[0_20px_48px_rgba(15,23,42,0.08)] {{ $open->deleted_at ? 'opacity-90' : '' }}"
                        style="--position-delay: {{ 210 + (($loop->index % 6) * 35) }}ms;"
                        data-search="{{ strtolower(trim(($open->title ?? '') . ' ' . ($open->department ?? '') . ' ' . ($open->employment ?? '') . ' ' . ($open->job_type ?? '') . ' ' . ($open->skills ?? '') . ' ' . ($open->location ?? ''))) }}"
                        data-department="{{ strtolower(trim((string) ($open->department ?? ''))) }}"
                        data-employment="{{ strtolower(trim((string) ($open->employment ?? ''))) }}"
                        data-job-type="{{ strtolower(trim((string) ($open->job_type ?? ''))) }}"
                        data-status="{{ $open->deleted_at ? 'closed' : 'active' }}"
                        data-applicants-count="{{ (int) ($open->applicants_count ?? 0) }}"
                    >
                        <div class="absolute inset-x-6 top-0 h-1 rounded-full {{ $open->deleted_at ? 'bg-[linear-gradient(90deg,#f59e0b,#ef4444)]' : 'bg-[linear-gradient(90deg,#0ea5e9,#10b981)]' }} opacity-80"></div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] {{ $open->deleted_at ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $open->deleted_at ? 'Closed' : 'Active' }}
                                    </span>
                                    @if (!$open->deleted_at && ($open->applicants_count ?? 0) >= 5)
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700">High Demand</span>
                                    @endif
                                </div>

                                <h3 class="mt-4 text-2xl font-black tracking-tight text-slate-900">{{ $open->title }}</h3>
                                <p class="mt-2 text-sm text-slate-500">{{ $open->department }} | {{ $open->employment }} | {{ $open->job_type }}</p>
                            </div>

                            <div class="flex h-14 w-14 items-center justify-center rounded-[1.35rem] {{ $open->deleted_at ? 'bg-rose-100 text-rose-600 group-hover:bg-rose-600' : 'bg-sky-100 text-sky-600 group-hover:bg-sky-600' }} transition group-hover:text-white">
                                <i class="fa-solid fa-briefcase text-lg"></i>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @if (!empty($open->location))
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                                    <i class="fa-solid fa-location-dot text-sky-500"></i>
                                    {{ $open->location }}
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                                <i class="fa-regular fa-calendar text-emerald-500"></i>
                                Posted {{ optional($open->created_at)->format('m/d/y') }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                                <i class="fa-solid fa-users text-indigo-500"></i>
                                {{ $open->applicants_count }} Applicants
                            </span>
                        </div>

                        <div class="mt-5 rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Role</p>
                            <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                                @forelse ($descriptionPreview as $previewLine)
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-sky-500"></span>
                                        <span>{{ $previewLine }}</span>
                                    </li>
                                @empty
                                    <li class="text-slate-400">No description preview available.</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach (collect(explode(',', (string) ($open->skills ?? '')))->map(fn ($skill) => trim($skill))->filter() as $skill)
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $skill }}</span>
                            @endforeach
                        </div>

                        <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-slate-500">
                                <p class="font-medium text-slate-700">{{ $open->department }}</p>
                                <p>{{ $open->deleted_at ? 'No longer visible on the applicant page' : 'Hiring pipeline ready for review' }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    onclick="window.location.href='{{ route('admin.adminShowPosition', $open->id) }}'"
                                    class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                                >
                                    <i class="fa-regular fa-eye text-xs"></i>
                                    View Details
                                </button>
                                @if ($open->deleted_at)
                                    <form action="{{ route('admin.restorePosition', $open->id) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                        >
                                            <i class="fa-solid fa-rotate-left text-xs"></i>
                                            Reopen
                                        </button>
                                    </form>
                                @else
                                    <button
                                        type="button"
                                        onclick="window.location.href='{{ route('admin.adminEditPosition', $open->id) }}'"
                                        class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                                    >
                                        <i class="fa-regular fa-pen-to-square text-xs"></i>
                                        Edit
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </main>
</div>

</body>

<script>
  (function () {
    const initPositionPageAnimation = () => {
      const page = document.getElementById('admin-position-page');
      if (!page) return;

      const revealItems = Array.from(page.querySelectorAll('.position-reveal'));
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
      document.addEventListener('DOMContentLoaded', initPositionPageAnimation, { once: true });
    } else {
      initPositionPageAnimation();
    }
  })();

  (function () {
    const searchInput = document.getElementById('positionSearchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    const employmentFilter = document.getElementById('employmentFilter');
    const jobTypeFilter = document.getElementById('jobTypeFilter');
    const resetButton = document.getElementById('resetPositionFilters');
    const cards = Array.from(document.querySelectorAll('.position-card'));
    const emptyState = document.getElementById('positionEmptyState');
    let activeDashboardFilter = 'all';

    function normalize(value) {
      return (value || '').toString().trim().toLowerCase();
    }

    function updateDashboardCardState() {
      document.querySelectorAll('[data-position-dashboard-filter]').forEach(card => {
        const isActive = card.dataset.positionDashboardFilter === activeDashboardFilter;
        card.classList.toggle('border-sky-300', isActive);
        card.classList.toggle('ring-2', isActive);
        card.classList.toggle('ring-sky-200', isActive);
        card.classList.toggle('bg-sky-50/70', isActive);
      });
    }

    function applyFilters() {
      const searchTerm = normalize(searchInput?.value);
      const department = normalize(departmentFilter?.value);
      const employment = normalize(employmentFilter?.value);
      const jobType = normalize(jobTypeFilter?.value);

      let visibleCount = 0;

      cards.forEach(card => {
        const matchesSearch = !searchTerm || normalize(card.dataset.search).includes(searchTerm);
        const matchesDepartment = !department || normalize(card.dataset.department) === department;
        const matchesEmployment = !employment || normalize(card.dataset.employment) === employment;
        const matchesJobType = !jobType || normalize(card.dataset.jobType) === jobType;
        const matchesDashboardFilter = (() => {
          if (activeDashboardFilter === 'active') {
            return normalize(card.dataset.status) === 'active';
          }

          if (activeDashboardFilter === 'closed') {
            return normalize(card.dataset.status) === 'closed';
          }

          if (activeDashboardFilter === 'applications') {
            return Number(card.dataset.applicantsCount || 0) > 0;
          }

          return true;
        })();

        const isVisible = matchesSearch && matchesDepartment && matchesEmployment && matchesJobType && matchesDashboardFilter;
        card.classList.toggle('hidden', !isVisible);

        if (isVisible) {
          visibleCount += 1;
        }
      });

      emptyState.classList.toggle('hidden', visibleCount !== 0);
      updateDashboardCardState();
    }

    function applyDashboardFilter(filter) {
      activeDashboardFilter = filter || 'all';
      if (searchInput) searchInput.value = '';
      if (departmentFilter) departmentFilter.value = '';
      if (employmentFilter) employmentFilter.value = '';
      if (jobTypeFilter) jobTypeFilter.value = '';
      applyFilters();
      document.getElementById('positionCardsGrid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    searchInput?.addEventListener('input', applyFilters);
    departmentFilter?.addEventListener('change', applyFilters);
    employmentFilter?.addEventListener('change', applyFilters);
    jobTypeFilter?.addEventListener('change', applyFilters);
    resetButton?.addEventListener('click', () => {
      activeDashboardFilter = 'all';
      searchInput.value = '';
      departmentFilter.value = '';
      employmentFilter.value = '';
      jobTypeFilter.value = '';
      applyFilters();
    });

    document.querySelectorAll('[data-position-dashboard-filter]').forEach(card => {
      const handleClick = () => applyDashboardFilter(card.dataset.positionDashboardFilter || 'all');
      card.addEventListener('click', handleClick);
      card.addEventListener('keydown', event => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        handleClick();
      });
    });

    applyFilters();
  })();
</script>

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
</script>
</html>
