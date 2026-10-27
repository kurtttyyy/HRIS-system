<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeopleHub - Resigned Employee Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
@include('components.adminSideBar')
<main class="ml-16 p-5 transition-all duration-300 md:p-8">
    <header class="rounded-3xl bg-slate-900 p-7 text-white md:p-9">
        <div class="text-xs font-bold uppercase tracking-widest text-emerald-300"><i class="fa-solid fa-box-archive mr-2"></i> Employee Records</div>
        <h1 class="mt-3 text-3xl font-black">Resigned Employee Archive</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Review approved and completed resignation records, including employee details and separation documents.</p>
        <div class="mt-5 flex flex-wrap items-center gap-4">
            <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">{{ $total }} archived {{ $total === 1 ? 'record' : 'records' }}</span>
            <a href="{{ route('admin.adminResignations', request()->only('tab_session')) }}" class="text-sm font-semibold text-emerald-300 hover:underline">Manage resignations <i class="fa-solid fa-arrow-right ml-1"></i></a>
        </div>
    </header>
    <form method="GET" class="my-6 flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        @if(request('tab_session')) <input type="hidden" name="tab_session" value="{{ request('tab_session') }}"> @endif
        <label class="min-w-0 flex-1"><span class="sr-only">Search employee records</span><input name="search" value="{{ $search }}" placeholder="Search name, employee ID, department or position" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></label>
        <label><span class="sr-only">Resignation status</span><select name="status" class="rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="">All archived statuses</option>@foreach(['Approved', 'Completed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></label>
        <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white">Search</button>
        <a href="{{ route('admin.resignations.archive', request()->only('tab_session')) }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm">Reset</a>
    </form>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-bold">Separation history</h2><p class="mt-1 text-sm text-slate-500">Identity details are preserved from the resignation record.</p></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-5">Employee</th><th class="p-5">Department / Position</th><th class="p-5">Effective date</th><th class="p-5">Status</th><th class="p-5">Record</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($records as $record)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="p-5"><div class="font-bold">{{ $record->employee_name ?: 'Employee name unavailable' }}</div><div class="mt-1 text-xs text-slate-500">{{ $record->employee_id ?: 'No employee ID' }}</div></td>
                        <td class="p-5"><div>{{ $record->department ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $record->position ?: '—' }}</div></td>
                        <td class="whitespace-nowrap p-5">{{ $record->effective_date?->format('M d, Y') ?? 'Not specified' }}</td>
                        <td class="p-5"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $record->status }}</span></td>
                        <td class="min-w-64 p-5"><details><summary class="cursor-pointer font-semibold text-emerald-700">View details</summary><div class="mt-3 space-y-3 text-sm"><p><strong>Submitted:</strong> {{ $record->submitted_at?->format('M d, Y') ?? '—' }}</p><div><strong>Reason</strong><p class="whitespace-pre-wrap text-slate-600">{{ $record->reason ?: 'No reason recorded.' }}</p></div><div><strong>HR note</strong><p class="whitespace-pre-wrap text-slate-600">{{ $record->admin_note ?: 'No note recorded.' }}</p></div>@if($record->attachment_path)<a target="_blank" rel="noopener" href="{{ route('admin.resignationAttachment.preview', ['resignation' => $record->id] + request()->only('tab_session')) }}" class="inline-block font-semibold text-emerald-700 hover:underline"><i class="fa-regular fa-file-lines mr-1"></i> View resignation letter</a>@endif</div></details></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-16 text-center"><i class="fa-solid fa-box-archive mb-4 text-3xl text-slate-300"></i><p class="font-bold">No archived records found</p><p class="mt-2 text-slate-500">Approved and completed resignations appear here automatically. Try adjusting your search.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-5">{{ $records->links() }}</div>
    </section>
</main>
</body>
</html>
