<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Job Details</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
  </style>
</head>

<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#f1f5f9_45%,#eefbf6_100%)]">
@php
    $descriptionLines = collect(preg_split("/\r\n|\n|\r/", (string) ($open->job_description ?? '')))
        ->map(fn ($line) => trim(preg_replace('/^[\s\-\*\x{2022}\x{2023}\x{25E6}\x{2043}]+/u', '', (string) $line)))
        ->filter(fn ($line) => $line !== '')
        ->values();

    $responsibilityLines = collect(preg_split("/\r\n|\n|\r/", (string) ($open->responsibilities ?? '')))
        ->map(fn ($line) => trim(preg_replace('/^[\s\-\*\x{2022}\x{2023}\x{25E6}\x{2043}]+/u', '', (string) $line)))
        ->filter(fn ($line) => $line !== '')
        ->values();

    $requirementLines = collect(preg_split("/\r\n|\n|\r/", (string) ($open->requirements ?? '')))
        ->map(fn ($line) => trim(preg_replace('/^[\s\-\*\x{2022}\x{2023}\x{25E6}\x{2043}]+/u', '', (string) $line)))
        ->filter(fn ($line) => $line !== '')
        ->values();

    $benefitLines = collect(preg_split("/\r\n|\n|\r/", (string) ($open->benifits ?? '')))
        ->map(fn ($line) => trim(preg_replace('/^[\s\-\*\x{2022}\x{2023}\x{25E6}\x{2043}]+/u', '', (string) $line)))
        ->filter(fn ($line) => $line !== '')
        ->values();

    $skillLines = collect(explode(',', (string) ($open->skills ?? '')))
        ->map(fn ($skill) => trim($skill))
        ->filter(fn ($skill) => $skill !== '')
        ->values();

    $initials = collect(explode(' ', trim((string) ($open->title ?? ''))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');

    $postedDate = !empty($open->one) ? \Carbon\Carbon::parse($open->one)->format('F j, Y') : optional($open->created_at)->format('F j, Y');
    $closingDate = !empty($open->two) ? \Carbon\Carbon::parse($open->two)->format('F j, Y') : 'Not set';
@endphp

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    <div class="space-y-6 p-4 pt-10 md:p-8">
      <a href="{{ route('admin.adminPosition') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Back to Jobs
      </a>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_370px]">
        <div class="space-y-6">
          <section class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_24px_55px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.1),_transparent_28%),linear-gradient(135deg,_rgba(248,250,252,0.96),_rgba(255,255,255,0.92))]"></div>
            <div class="relative">
              <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                  <div class="flex h-16 w-16 items-center justify-center rounded-[1.5rem] bg-[linear-gradient(135deg,#0ea5e9,#2563eb)] text-xl font-black text-white shadow-lg">
                    {{ $initials !== '' ? $initials : 'JB' }}
                  </div>

                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] {{ $open->deleted_at ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ $open->deleted_at ? 'Closed' : 'Active' }}
                      </span>
                      <span class="rounded-full border border-slate-200 bg-white/85 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $open->job_type }}</span>
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900 md:text-4xl">{{ $open->title }}</h1>
                    <p class="mt-2 text-sm text-slate-600 md:text-base">{{ $open->department }} | {{ $open->employment }}</p>

                    <div class="mt-4 flex flex-wrap gap-2">
                      @if (!empty($open->location))
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-medium text-slate-600">
                          <i class="fa-solid fa-location-dot text-sky-500"></i>
                          {{ $open->work_mode }}{{ !empty($open->location) ? ' | ' . $open->location : '' }}
                        </span>
                      @endif
                      <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-medium text-slate-600">
                        <i class="fa-regular fa-calendar text-emerald-500"></i>
                        Posted {{ $postedDate }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-wrap gap-3">
                  @if ($open->deleted_at)
                    <form action="{{ route('admin.restorePosition', $open->id) }}" method="POST">
                      @csrf
                      <button type="submit" class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                        Reopen Position
                      </button>
                    </form>
                  @else
                    <a href="{{ route('admin.adminEditPosition', $open->id) }}" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                      <i class="fa-regular fa-pen-to-square text-xs"></i>
                      Edit Job
                    </a>
                  @endif
                  @if (!$open->deleted_at)
                    <form action="{{ route('admin.destroyPosition', $open->id) }}" method="POST">
                      @csrf
                      <button type="submit" class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-white px-5 py-3 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                        <i class="fa-solid fa-ban text-xs"></i>
                        Close Position
                      </button>
                    </form>
                  @else
                    <span class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-600">
                      <i class="fa-solid fa-circle-stop text-xs"></i>
                      Applicant View Closed
                    </span>
                  @endif
                </div>
              </div>

              <div class="mt-8 grid gap-4 border-t border-slate-200 pt-6 md:grid-cols-3">
                <div class="rounded-[1.5rem] border border-slate-200 bg-white/80 p-4 text-center">
                  <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                    <i class="fa-solid fa-users"></i>
                  </div>
                  <p class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $countApplication }}</p>
                  <p class="mt-1 text-sm text-slate-500">Total Applicants</p>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white/80 p-4 text-center">
                  <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                    <i class="fa-regular fa-folder-open"></i>
                  </div>
                  <p class="mt-3 text-3xl font-black tracking-tight text-slate-900">{{ $countApplication }}</p>
                  <p class="mt-1 text-sm text-slate-500">In Review Pipeline</p>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white/80 p-4 text-center">
                  <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                    <i class="fa-regular fa-clock"></i>
                  </div>
                  <p class="mt-3 text-2xl font-black tracking-tight text-slate-900">{{ optional($open->created_at)->format('M. j, Y') }}</p>
                  <p class="mt-1 text-sm text-slate-500">Published</p>
                </div>
              </div>
            </div>
          </section>

          <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="space-y-8">
              <div>
                <div class="flex items-center gap-3">
                  <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                    <i class="fa-regular fa-file-lines"></i>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Role Summary</p>
                    <h2 class="text-xl font-black tracking-tight text-slate-900">Job Description</h2>
                  </div>
                </div>
                <div class="mt-5 rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                  @if ($descriptionLines->isNotEmpty())
                    <div class="space-y-3 text-sm leading-7 text-slate-600">
                      @foreach ($descriptionLines as $line)
                        <p>{{ $line }}</p>
                      @endforeach
                    </div>
                  @else
                    <p class="text-sm text-slate-400">No job description provided.</p>
                  @endif
                </div>
              </div>

              <div>
                <div class="flex items-center gap-3">
                  <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                    <i class="fa-solid fa-list-check"></i>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Execution</p>
                    <h2 class="text-xl font-black tracking-tight text-slate-900">Responsibilities</h2>
                  </div>
                </div>
                <div class="mt-5 grid gap-3">
                  @forelse ($responsibilityLines as $line)
                    <div class="flex gap-3 rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm">
                      <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fa-solid fa-check text-xs"></i>
                      </span>
                      <p class="text-sm leading-6 text-slate-600">{{ $line }}</p>
                    </div>
                  @empty
                    <p class="text-sm text-slate-400">No responsibilities listed.</p>
                  @endforelse
                </div>
              </div>

              <div>
                <div class="flex items-center gap-3">
                  <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                    <i class="fa-solid fa-shield-halved"></i>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Candidate Fit</p>
                    <h2 class="text-xl font-black tracking-tight text-slate-900">Requirements</h2>
                  </div>
                </div>
                <div class="mt-5 grid gap-3">
                  @forelse ($requirementLines as $line)
                    <div class="flex gap-3 rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm">
                      <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <i class="fa-solid fa-check text-xs"></i>
                      </span>
                      <p class="text-sm leading-6 text-slate-600">{{ $line }}</p>
                    </div>
                  @empty
                    <p class="text-sm text-slate-400">No requirements listed.</p>
                  @endforelse
                </div>
              </div>
            </div>
          </section>
        </div>

        <div class="space-y-6">
          <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                <i class="fa-solid fa-briefcase"></i>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Position Details</p>
                <h3 class="text-xl font-black tracking-tight text-slate-900">Job Details</h3>
              </div>
            </div>

            <div class="mt-5 space-y-4">
              <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Experience Level</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $open->experience_level ?: 'Not specified' }}</p>
              </div>
              <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Location</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $open->work_mode ?: 'Not specified' }}{{ !empty($open->location) ? ' (' . $open->location . ')' : '' }}</p>
              </div>
              <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Posted Date</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $postedDate }}</p>
              </div>
              <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Closing Date</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $closingDate }}</p>
              </div>
            </div>
          </section>

          <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                <i class="fa-solid fa-code"></i>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Candidate Match</p>
                <h3 class="text-xl font-black tracking-tight text-slate-900">Required Skills</h3>
              </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
              @forelse ($skillLines as $skill)
                <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700">{{ $skill }}</span>
              @empty
                <p class="text-sm text-slate-400">No skills listed.</p>
              @endforelse
            </div>
          </section>

          <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                <i class="fa-solid fa-gift"></i>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Offer Package</p>
                <h3 class="text-xl font-black tracking-tight text-slate-900">Benefits & Perks</h3>
              </div>
            </div>

            <div class="mt-5 grid gap-3">
              @forelse ($benefitLines as $line)
                <div class="flex gap-3 rounded-[1.25rem] border border-emerald-200 bg-emerald-50/60 p-4">
                  <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-white text-emerald-600">
                    <i class="fa-solid fa-check text-xs"></i>
                  </span>
                  <p class="text-sm leading-6 text-slate-600">{{ $line }}</p>
                </div>
              @empty
                <p class="text-sm text-slate-400">No benefits listed.</p>
              @endforelse
            </div>
          </section>

          <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                <i class="fa-solid fa-user-group"></i>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Support Team</p>
                <h3 class="text-xl font-black tracking-tight text-slate-900">Hiring Team</h3>
              </div>
            </div>

            <div class="mt-5 space-y-3">
              @foreach ($admin as $team)
                @php
                    $teamInitials = collect(explode(' ', trim(($team->first_name ?? '') . ' ' . ($team->last_name ?? ''))))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->implode('');
                @endphp
                <div class="flex items-center gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                  <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[linear-gradient(135deg,#6366f1,#3b82f6)] font-semibold text-white">
                    {{ $teamInitials !== '' ? $teamInitials : 'HR' }}
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $team->first_name }} {{ $team->last_name }}</p>
                    <p class="text-xs text-slate-500">{{ $team->job_role ?: 'Hiring coordinator' }}</p>
                  </div>
                </div>
              @endforeach
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>
</div>

</body>

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
