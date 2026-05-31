<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Edit Position</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    :root {
      --ink: #0f172a;
      --muted: #64748b;
      --line: #dbe4ee;
      --panel: rgba(255, 255, 255, 0.9);
      --teal: #0f766e;
      --teal-dark: #115e59;
    }

    body {
      font-family: Inter, "Segoe UI", system-ui, sans-serif;
      background:
        radial-gradient(circle at top left, rgba(14, 165, 233, 0.11), transparent 28%),
        radial-gradient(circle at top right, rgba(16, 185, 129, 0.14), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #eef5f2 100%);
      color: var(--ink);
      transition: margin-left 0.3s ease;
    }

    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }

    .edit-shell {
      max-width: 92rem;
      margin: 0 auto;
    }

    .glass-panel {
      border: 1px solid rgba(148, 163, 184, 0.24);
      background: var(--panel);
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.07);
      backdrop-filter: blur(14px);
    }

    .field-label {
      display: block;
      margin-bottom: 0.45rem;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      color: #334155;
    }

    .field-control {
      width: 100%;
      border: 1px solid rgba(148, 163, 184, 0.42);
      border-radius: 0.9rem;
      background: rgba(255, 255, 255, 0.92);
      padding: 0.82rem 0.95rem;
      font-size: 0.95rem;
      color: var(--ink);
      transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
    }

    .field-control:focus {
      outline: none;
      border-color: rgba(15, 118, 110, 0.72);
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.11);
    }

    textarea.field-control {
      min-height: 9rem;
      line-height: 1.65;
      resize: vertical;
    }

    .section-title {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1.3rem;
    }

    .section-icon {
      display: inline-flex;
      height: 2.35rem;
      width: 2.35rem;
      align-items: center;
      justify-content: center;
      border-radius: 0.9rem;
      background: rgba(15, 118, 110, 0.1);
      color: var(--teal);
    }

    .summary-item {
      border: 1px solid rgba(148, 163, 184, 0.26);
      border-radius: 1rem;
      background: rgba(248, 250, 252, 0.76);
      padding: 0.95rem;
    }
  </style>
</head>

<body class="min-h-screen">
@php
    $titleValue = old('title', $open->title);
    $departmentValue = old('department', $open->department);
    $employmentValue = old('employment', $open->employment);
    $jobTypeValue = old('job_type', $open->job_type);
    $experienceValue = old('experience_level', $open->experience_level);
    $locationValue = old('location', $open->location);
    $skillsValue = old('skills', $open->skills);
    $skillsPreview = collect(explode(',', (string) $skillsValue))
        ->map(fn ($skill) => trim($skill))
        ->filter(fn ($skill) => $skill !== '')
        ->values();
    $roleInitials = collect(explode(' ', trim((string) $titleValue)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    <div class="p-4 pt-8 md:p-8">
      <div class="edit-shell space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <a href="{{ route('admin.adminShowPosition', $open->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-teal-200 hover:text-teal-700">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Back to Position
          </a>
          <div class="hidden items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-400 md:flex">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            Position Editor
          </div>
        </div>

        <form action="{{ route('admin.updatePosition', $open->id) }}" method="POST" class="space-y-5">
          @csrf

          @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 shadow-sm">
              {{ $errors->first() }}
            </div>
          @endif

          <section class="glass-panel overflow-hidden rounded-3xl">
            <div class="grid gap-6 p-6 md:grid-cols-[1fr_auto] md:p-7">
              <div class="flex gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#0f766e,#2563eb)] text-xl font-black text-white shadow-lg">
                  {{ $roleInitials !== '' ? $roleInitials : 'JP' }}
                </div>
                <div>
                  <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-teal-700">{{ $jobTypeValue ?: 'Job Type' }}</span>
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">{{ $employmentValue ?: 'Employment' }}</span>
                  </div>
                  <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 md:text-4xl">Edit Job Posting</h1>
                  <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Update the vacancy details shown to applicants. Keep the role clear, searchable, and ready for review.
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <a href="{{ route('admin.adminShowPosition', $open->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition hover:text-slate-900">
                  <i class="fa-solid fa-xmark text-xs"></i>
                  Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:bg-teal-800">
                  <i class="fa-solid fa-floppy-disk text-xs"></i>
                  Save Changes
                </button>
              </div>
            </div>
          </section>

          <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-5">
              <section class="glass-panel rounded-3xl p-6">
                <div class="section-title">
                  <span class="section-icon"><i class="fa-solid fa-circle-info"></i></span>
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Core Details</p>
                    <h2 class="text-xl font-black tracking-tight">Basic Information</h2>
                  </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                  <div class="md:col-span-2">
                    <label class="field-label">Job Title</label>
                    <input class="field-control" type="text" name="title" value="{{ $titleValue }}">
                  </div>
                  <div>
                    <label class="field-label">Department</label>
                    <input class="field-control" type="text" name="department" value="{{ $departmentValue }}">
                  </div>
                  <div>
                    <label class="field-label">Employment Type</label>
                    <select class="field-control" name="employment">
                      <option value="">Select employment type</option>
                      <option value="Full-Time" {{ $employmentValue == 'Full-Time' ? 'selected' : '' }}>Full-Time</option>
                      <option value="Part-Time" {{ $employmentValue == 'Part-Time' ? 'selected' : '' }}>Part-Time</option>
                      <option value="Contract" {{ $employmentValue == 'Contract' ? 'selected' : '' }}>Contract</option>
                    </select>
                  </div>
                  <div>
                    <label class="field-label">Job Type</label>
                    <select class="field-control" name="job_type">
                      <option value="">Select job type</option>
                      <option value="Teaching" {{ $jobTypeValue == 'Teaching' ? 'selected' : '' }}>Teaching</option>
                      <option value="Non-Teaching" {{ $jobTypeValue == 'Non-Teaching' ? 'selected' : '' }}>Non-Teaching</option>
                    </select>
                  </div>
                  <div>
                    <label class="field-label">Experience Level</label>
                    <select class="field-control" name="experience_level">
                      <option value="">Select experience level</option>
                      <option value="Senior" {{ in_array(strtolower((string) $experienceValue), ['senior', 'senior level'], true) ? 'selected' : '' }}>Senior</option>
                      <option value="Mid" {{ in_array(strtolower((string) $experienceValue), ['mid', 'mid level'], true) ? 'selected' : '' }}>Mid</option>
                      <option value="Junior" {{ in_array(strtolower((string) $experienceValue), ['junior', 'junior level'], true) ? 'selected' : '' }}>Junior</option>
                    </select>
                  </div>
                  <div class="md:col-span-2">
                    <label class="field-label">Location</label>
                    <input class="field-control" type="text" name="location" value="{{ $locationValue }}">
                  </div>
                </div>
              </section>

              <section class="glass-panel rounded-3xl p-6">
                <div class="section-title">
                  <span class="section-icon"><i class="fa-regular fa-file-lines"></i></span>
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Role Story</p>
                    <h2 class="text-xl font-black tracking-tight">Description</h2>
                  </div>
                </div>
                <label class="field-label">Job Description</label>
                <textarea class="field-control" name="job_description">{{ old('job_description', $open->job_description) }}</textarea>
              </section>

              <section class="glass-panel rounded-3xl p-6">
                <div class="section-title">
                  <span class="section-icon"><i class="fa-solid fa-list-check"></i></span>
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Expectations</p>
                    <h2 class="text-xl font-black tracking-tight">Responsibilities and Requirements</h2>
                  </div>
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                  <div>
                    <label class="field-label">Key Responsibilities</label>
                    <textarea class="field-control" name="responsibilities">{{ old('responsibilities', $open->responsibilities) }}</textarea>
                  </div>
                  <div>
                    <label class="field-label">Requirements</label>
                    <textarea class="field-control" name="requirements">{{ old('requirements', $open->requirements) }}</textarea>
                  </div>
                </div>
              </section>

              <section class="glass-panel rounded-3xl p-6">
                <div class="section-title">
                  <span class="section-icon"><i class="fa-solid fa-calendar-days"></i></span>
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Timeline</p>
                    <h2 class="text-xl font-black tracking-tight">Posting Schedule</h2>
                  </div>
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                  <div>
                    <label class="field-label">Posted Date</label>
                    <input class="field-control" type="date" name="one" value="{{ old('one', $open->one ? \Carbon\Carbon::parse($open->one)->format('Y-m-d') : '') }}">
                  </div>
                  <div>
                    <label class="field-label">Closing Date</label>
                    <input class="field-control" type="date" name="two" value="{{ old('two', $open->two ? \Carbon\Carbon::parse($open->two)->format('Y-m-d') : '') }}">
                  </div>
                </div>
              </section>
            </div>

            <aside class="space-y-5 xl:sticky xl:top-8 xl:self-start">
              <section class="glass-panel rounded-3xl p-5">
                <div class="section-title mb-4">
                  <span class="section-icon"><i class="fa-solid fa-table-columns"></i></span>
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Preview</p>
                    <h3 class="text-lg font-black tracking-tight">Position Summary</h3>
                  </div>
                </div>
                <div class="space-y-3">
                  <div class="summary-item">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Role</p>
                    <p class="mt-1 text-sm font-bold">{{ $titleValue ?: 'Untitled Position' }}</p>
                  </div>
                  <div class="summary-item">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Department</p>
                    <p class="mt-1 text-sm font-bold">{{ $departmentValue ?: 'Not specified' }}</p>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="summary-item">
                      <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Type</p>
                      <p class="mt-1 text-sm font-bold">{{ $jobTypeValue ?: '-' }}</p>
                    </div>
                    <div class="summary-item">
                      <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Level</p>
                      <p class="mt-1 text-sm font-bold">{{ $experienceValue ?: '-' }}</p>
                    </div>
                  </div>
                  <div class="summary-item">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Location</p>
                    <p class="mt-1 text-sm font-bold">{{ $locationValue ?: 'Not specified' }}</p>
                  </div>
                </div>
              </section>

              <section class="glass-panel rounded-3xl p-5">
                <div class="section-title mb-4">
                  <span class="section-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Details</p>
                    <h3 class="text-lg font-black tracking-tight">Skills and Benefits</h3>
                  </div>
                </div>
                <label class="field-label">Skills</label>
                <input class="field-control" type="text" name="skills" value="{{ $skillsValue }}">
                <div class="mt-3 flex flex-wrap gap-2">
                  @forelse ($skillsPreview as $skill)
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $skill }}</span>
                  @empty
                    <span class="text-sm text-slate-400">No skills added yet.</span>
                  @endforelse
                </div>

                <label class="field-label mt-5">Benefits</label>
                <textarea class="field-control min-h-[7rem]" name="benifits">{{ old('benifits', $open->benifits) }}</textarea>
              </section>

              <div class="glass-panel rounded-3xl p-4">
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[linear-gradient(135deg,#0f766e,#0f172a)] px-5 py-3 text-sm font-black text-white shadow-lg shadow-teal-900/15 transition hover:-translate-y-0.5">
                  <i class="fa-solid fa-floppy-disk text-xs"></i>
                  Save Position Updates
                </button>
              </div>
            </aside>
          </div>
        </form>
      </div>
    </div>
  </main>
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
</script>
</body>
</html>
