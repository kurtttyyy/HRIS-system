<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Applicant History - PeopleHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body { font-family: Inter, system-ui, sans-serif; }
    main { transition: margin-left .3s ease; }
    .history-hero { background: radial-gradient(ellipse at top right, rgba(16,185,129,.22), transparent 65%), linear-gradient(120deg,#071522,#05382e); }
    .history-filters input, .history-filters select { width: 100%; min-height: 44px; outline: none; transition: border-color .2s, box-shadow .2s; }
    .history-filters input:focus, .history-filters select:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(16,185,129,.12); }
    .history-filters label { display: block; margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #475569; }
    .history-table td { vertical-align: middle; }
    .history-table tbody tr { transition: background-color .2s; }
  </style>
</head>
<body class="bg-slate-100 text-slate-900">
  @include('components.adminSideBar')
  <main class="min-h-screen p-4 pt-20 md:p-8 md:pt-8 lg:ml-16">
    <header class="history-hero rounded-[2rem] p-6 text-white shadow-lg md:p-8">
      <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[.22em] text-emerald-200"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Hiring / History</p>
      <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black tracking-tight">Applicant History</h1>
          <p class="mt-2 text-sm text-emerald-50/80">View applicants whose applications were rejected, including archived records.</p>
        </div>
        <a href="{{ route('admin.adminApplicant', request()->only('tab_session')) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-4 py-3 text-sm font-semibold transition hover:bg-white/10"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Applicants</a>
      </div>
    </header>

    <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-6">
        <div class="flex items-center gap-3">
          <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-500"><i class="fa-solid fa-folder-closed" aria-hidden="true"></i></span>
          <div><h2 class="text-lg font-bold">Rejected Applications</h2>
          <p class="mt-1 text-sm text-slate-500">{{ number_format($applicants->total()) }} {{ $search !== '' || $year || $month ? 'matching' : 'rejected' }} application{{ $applicants->total() === 1 ? '' : 's' }}</p>
          </div>
        </div>
        <span class="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"><i class="fa-solid fa-box-archive mr-1" aria-hidden="true"></i> Archived records included</span>
      </div>
      <div class="border-b border-slate-200 bg-slate-50/70 p-6">
        <form method="GET" action="{{ route('admin.adminApplicant.history') }}" class="history-filters grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[minmax(240px,2fr)_minmax(130px,1fr)_minmax(150px,1fr)_auto]">
          @if(request()->query('tab_session'))
            <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
          @endif
          <div><label for="historySearch">Search applicant history</label>
          <input id="historySearch" name="search" value="{{ $search }}" maxlength="200" placeholder="Search name, phone, position or degree" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-2 text-sm sm:w-80">
          </div><div><label for="historyYear">Submission year</label>
          <select id="historyYear" name="year" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">All years</option>
            @foreach($years as $optionYear)
              <option value="{{ $optionYear }}" @selected($year === $optionYear)>{{ $optionYear }}</option>
            @endforeach
          </select>
          </div><div><label for="historyMonth">Submission month</label>
          <select id="historyMonth" name="month" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">All months</option>
            @foreach([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $optionMonth => $monthLabel)
              <option value="{{ $optionMonth }}" @selected($month === $optionMonth)>{{ $monthLabel }}</option>
            @endforeach
          </select>
          </div><div class="flex items-center gap-2">
          <button class="min-h-[44px] flex-1 whitespace-nowrap rounded-xl bg-emerald-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800"><i class="fa-solid fa-filter mr-2" aria-hidden="true"></i>Apply filters</button>
          @if($search !== '' || $year || $month)
            <a href="{{ route('admin.adminApplicant.history', request()->only('tab_session')) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm hover:bg-slate-100">Clear</a>
          @endif
          </div>
        </form>
        <p class="mt-3 flex items-center gap-2 text-xs text-slate-500"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Filter by application submission date. Leave year or month blank to include all dates.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="history-table w-full min-w-[900px] text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr><th scope="col" class="px-6 py-4">Full name</th><th scope="col" class="px-6 py-4">Phone number</th><th scope="col" class="px-6 py-4">Position applied</th><th scope="col" class="px-6 py-4">Degree</th><th scope="col" class="px-6 py-4">Submitted</th></tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($applicants as $applicant)
              @php
                $fullName = collect([$applicant->first_name, $applicant->middle_name, $applicant->last_name, $applicant->name_extension])->map(fn ($part) => trim((string) $part))->filter()->implode(' ');
                $isEnteredDegree = fn ($degree) => !in_array(strtolower(trim((string) $degree)), ['', 'elementary', 'secondary', 'college', 'vocational / trade course', 'n/a', 'na', 'none', 'not provided', 'not applicable', '-'], true);
                $degreeNames = $applicant->degrees->pluck('degree_name')->merge([$applicant->bachelor_degree, $applicant->master_degree, $applicant->doctoral_degree])->map(fn ($degree) => trim((string) $degree))->filter($isEnteredDegree)->unique()->values();
                if ($degreeNames->isEmpty() && $isEnteredDegree($applicant->field_study)) { $degreeNames->push(trim((string) $applicant->field_study)); }
              @endphp
              <tr class="hover:bg-emerald-50/50">
                <td class="px-6 py-5"><div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-700">{{ mb_strtoupper(mb_substr(trim((string) $applicant->first_name), 0, 1).mb_substr(trim((string) $applicant->last_name), 0, 1)) ?: '?' }}</span><div><p class="font-semibold">{{ $fullName ?: 'Name not provided' }}</p><span class="mt-1 inline-flex items-center gap-1.5 text-xs text-rose-600"><span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>Rejected</span></div></div></td>
                <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ $applicant->phone ?: 'Not provided' }}</td>
                <td class="px-6 py-4">{{ $applicant->position?->title ?: 'Not available' }}</td>
                <td class="px-6 py-4">
                  @forelse($degreeNames as $degree)
                    <div class="leading-6">{{ $degree }}</div>
                  @empty
                    <span class="text-slate-500">Not provided</span>
                  @endforelse
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-slate-500">{{ $applicant->created_at?->format('M d, Y') ?: 'Not provided' }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="px-6 py-16 text-center text-slate-500"><span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></span><p class="font-semibold text-slate-700">{{ $search !== '' || $year || $month ? 'No rejected applications match your filters.' : 'No rejected applications yet.' }}</p><p class="mt-2 text-xs">{{ $search !== '' || $year || $month ? 'Try another date range or clear the filters.' : 'Rejected applicants will appear here automatically.' }}</p></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
        <p class="text-xs text-slate-500">Showing {{ $applicants->firstItem() ?? 0 }}–{{ $applicants->lastItem() ?? 0 }} of {{ number_format($applicants->total()) }} applications</p>
        @if($applicants->hasPages()) <div>{{ $applicants->links() }}</div> @endif
      </div>
    </section>
  </main>
</body>
</html>
