<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub - Edit Position</title>

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
    $experienceLevel = strtolower(trim((string) old('experience_level', $open->experience_level)));
    $skillsPreview = collect(explode(',', (string) old('skills', $open->skills)))
        ->map(fn ($skill) => trim($skill))
        ->filter(fn ($skill) => $skill !== '')
        ->values();
    $roleInitials = collect(explode(' ', trim((string) old('title', $open->title))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="flex min-h-screen">
    @include('components.adminSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        <div class="space-y-6 p-4 pt-10 md:p-8">
            <a href="{{ route('admin.adminShowPosition', $open->id) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                Back to Position
            </a>

            <form class="space-y-6" action="{{ route('admin.updatePosition', $open->id) }}" method="POST">
                @csrf

                @if ($errors->any())
                    <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <section class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_24px_55px_rgba(15,23,42,0.08)] backdrop-blur">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.1),_transparent_28%),linear-gradient(135deg,_rgba(248,250,252,0.96),_rgba(255,255,255,0.92))]"></div>
                    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-[1.5rem] bg-[linear-gradient(135deg,#0ea5e9,#2563eb)] text-xl font-black text-white shadow-lg">
                                {{ $roleInitials !== '' ? $roleInitials : 'JP' }}
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-sky-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-sky-700">Position Editor</span>
                                    <span class="rounded-full border border-slate-200 bg-white/85 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ old('job_type', $open->job_type) ?: 'Job Type' }}</span>
                                </div>

                                <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900 md:text-4xl">Edit Job Posting</h1>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 md:text-base">
                                    Refine the role details, update the hiring brief, and keep the position listing polished before applicants see the changes.
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-medium text-slate-600">
                                        <i class="fa-solid fa-building text-sky-500"></i>
                                        {{ old('department', $open->department) ?: 'Department' }}
                                    </span>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-medium text-slate-600">
                                        <i class="fa-solid fa-briefcase text-emerald-500"></i>
                                        {{ old('employment', $open->employment) ?: 'Employment' }}
                                    </span>
                                </div>

                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('admin.adminShowPosition', $open->id) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                <i class="fa-solid fa-xmark text-xs"></i>
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                <i class="fa-solid fa-floppy-disk text-xs"></i>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_360px]">
                    <div class="space-y-6">
                        <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                                    <i class="fa-solid fa-circle-info"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Core Setup</p>
                                    <h2 class="text-xl font-black tracking-tight text-slate-900">Basic Information</h2>
                                </div>
                            </div>

                            <div class="mt-6 space-y-5">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Job Title</label>
                                    <input type="text" name="title" value="{{ old('title', $open->title) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                </div>

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Department</label>
                                        <input type="text" name="department" value="{{ old('department', $open->department) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Employment Type</label>
                                        <select name="employment" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                            <option value="">Select employment type</option>
                                            <option value="Full-Time" {{ old('employment', $open->employment) == 'Full-Time' ? 'selected' : '' }}>Full-Time</option>
                                            <option value="Part-Time" {{ old('employment', $open->employment) == 'Part-Time' ? 'selected' : '' }}>Part-Time</option>
                                            <option value="Contract" {{ old('employment', $open->employment) == 'Contract' ? 'selected' : '' }}>Contract</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Job Type</label>
                                        <select name="job_type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                            <option value="">Select job type</option>
                                            <option value="Teaching" {{ old('job_type', $open->job_type) == 'Teaching' ? 'selected' : '' }}>Teaching</option>
                                            <option value="Non-Teaching" {{ old('job_type', $open->job_type) == 'Non-Teaching' ? 'selected' : '' }}>Non-Teaching</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Location</label>
                                        <input type="text" name="location" value="{{ old('location', $open->location) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Experience Level</label>
                                        <select name="experience_level" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                            <option value="">Select experience level</option>
                                            <option value="Senior" {{ in_array($experienceLevel, ['senior', 'senior level'], true) ? 'selected' : '' }}>Senior</option>
                                            <option value="Mid" {{ in_array($experienceLevel, ['mid', 'mid level'], true) ? 'selected' : '' }}>Mid</option>
                                            <option value="Junior" {{ in_array($experienceLevel, ['junior', 'junior level'], true) ? 'selected' : '' }}>Junior</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                                    <i class="fa-regular fa-file-lines"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Role Story</p>
                                    <h2 class="text-xl font-black tracking-tight text-slate-900">Role Overview</h2>
                                </div>
                            </div>

                            <div class="mt-6 space-y-5">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Job Description</label>
                                    <textarea rows="6" name="job_description" class="w-full rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">{{ old('job_description', $open->job_description) }}</textarea>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Role Expectations</p>
                                    <h2 class="text-xl font-black tracking-tight text-slate-900">Responsibilities and Requirements</h2>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Key Responsibilities</label>
                                    <textarea rows="6" name="responsibilities" class="w-full rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">{{ old('responsibilities', $open->responsibilities) }}</textarea>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Requirements</label>
                                    <textarea rows="6" name="requirements" class="w-full rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">{{ old('requirements', $open->requirements) }}</textarea>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Timeline</p>
                                    <h2 class="text-xl font-black tracking-tight text-slate-900">Posting Schedule</h2>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Posted Date</label>
                                    <input type="date" name="one" value="{{ old('one', $open->one ? \Carbon\Carbon::parse($open->one)->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Closing Date</label>
                                    <input type="date" name="two" value="{{ old('two', $open->two ? \Carbon\Carbon::parse($open->two)->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                </div>
                            </div>
                        </section>

                        <div class="flex flex-wrap justify-end gap-3">
                            <a href="{{ route('admin.adminShowPosition', $open->id) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                <i class="fa-solid fa-xmark text-xs"></i>
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                <i class="fa-solid fa-floppy-disk text-xs"></i>
                                Save Changes
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6 xl:sticky xl:top-8 xl:self-start">
                        <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                                    <i class="fa-solid fa-table-columns"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Live Snapshot</p>
                                    <h3 class="text-xl font-black tracking-tight text-slate-900">Position Summary</h3>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Role</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ old('title', $open->title) ?: 'Untitled Position' }}</p>
                                </div>
                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Department</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ old('department', $open->department) ?: 'Not specified' }}</p>
                                </div>
                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Employment</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ old('employment', $open->employment) ?: 'Not specified' }}</p>
                                </div>
                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Experience Level</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ old('experience_level', $open->experience_level) ?: 'Not specified' }}</p>
                                </div>
                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Location</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ old('location', $open->location) ?: 'Not specified' }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                                    <i class="fa-solid fa-sparkles"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Skill Preview</p>
                                    <h3 class="text-xl font-black tracking-tight text-slate-900">Required Skills</h3>
                                </div>
                            </div>

                            <div class="mt-5">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Skills (comma separated)</label>
                                <input type="text" name="skills" value="{{ old('skills', $open->skills) }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @forelse ($skillsPreview as $skill)
                                        <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700">{{ $skill }}</span>
                                    @empty
                                        <p class="text-sm text-slate-400">No skills added yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                                    <i class="fa-solid fa-gift"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Offer Package</p>
                                    <h3 class="text-xl font-black tracking-tight text-slate-900">Benefits and Perks</h3>
                                </div>
                            </div>

                            <div class="mt-5">
                                <textarea rows="6" name="benifits" class="w-full rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 text-slate-800 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100">{{ old('benifits', $open->benifits) }}</textarea>
                            </div>
                        </section>
                    </div>
                </div>
            </form>
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
