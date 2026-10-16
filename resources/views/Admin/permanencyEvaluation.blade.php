<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Setup | Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>main { margin-left: 4rem; transition: margin-left .3s ease; } aside:hover ~ main { margin-left: 18rem; }</style>
    @include('components.evaluationPageMotion')
</head>
<body class="bg-slate-100 text-slate-900">
<div class="flex min-h-screen">
    @include('components.adminSideBar')
    <main class="min-w-0 flex-1 p-4 pt-20 md:p-8 md:pt-20 lg:pt-8">
        <div class="evaluation-page-shell mx-auto max-w-6xl space-y-6">
            <header class="rounded-3xl bg-emerald-900 p-6 text-white md:p-8">
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-100">Employee development</p>
                <h1 class="mt-2 text-3xl font-bold">Evaluation Setup</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-emerald-50">Create and publish custom evaluation questions. Department heads can select an employee on their Evaluation page to answer these questions.</p>
            </header>
            <nav aria-label="Evaluation pages" class="evaluation-tabs flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2">
                <a href="{{ route('admin.employeeEvaluation.customize') }}" aria-current="page" class="rounded-xl px-4 py-3 text-sm font-semibold bg-emerald-700 text-white"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Create evaluation</a>
                <a href="{{ route('admin.employeeEvaluation.reviews') }}"  class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>Submitted evaluations</a>
            </nav>
            @if (session('success'))<p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @include('components.evaluationAutoRefresh')
            <div id="evaluation-template-region" data-evaluation-refresh>
            <form method="POST" action="{{ route('admin.employeeEvaluation.publish') }}" class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6">
                @csrf
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-bold">Evaluation form</h2><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $template ? 'Published on '.$template->created_at->format('M j, Y') : 'Not published yet' }}</span></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="block text-sm font-semibold">Form title<input name="title" required maxlength="150" value="{{ old('title', $template?->title ?? 'Employee Evaluation') }}" class="mt-2 w-full rounded-xl border border-slate-300 p-3"></label>
                    <label class="block text-sm font-semibold">Notice days for permanency reviews<input type="number" name="notice_days" min="0" max="90" required value="{{ old('notice_days', $template?->notice_days ?? 7) }}" class="mt-2 w-full rounded-xl border border-slate-300 p-3"><span class="mt-1 block text-xs font-normal text-slate-500">Applies to the permanency review schedule. Team evaluation questions are available immediately after publishing.</span></label>
                </div>
                <label class="block text-sm font-semibold">Instructions for department heads<textarea name="instructions" rows="3" maxlength="3000" class="mt-2 w-full rounded-xl border border-slate-300 p-3">{{ old('instructions', $template?->instructions) }}</textarea></label>
                <div><h3 class="font-bold">Evaluation questions</h3><p class="mt-1 text-sm text-slate-500">Each question uses a scale from 1 to 5 and contributes equally to the overall rating.</p></div>
                <div id="criteria-list" class="space-y-3">
                    @foreach (old('criteria', $template?->criteria ?? [['label' => 'Quality of work', 'description' => 'Accuracy, completeness, and consistency.'], ['label' => 'Attendance and reliability', 'description' => 'Punctuality, attendance, and dependability.'], ['label' => 'Teamwork and conduct', 'description' => 'Collaboration, professionalism, and communication.']]) as $index => $criterion)
                        <div data-criterion class="grid gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[1fr_1fr_auto]">
                            <label class="text-sm font-semibold">Question<input data-field="label" name="criteria[{{ $index }}][label]" required maxlength="150" value="{{ $criterion['label'] ?? '' }}" class="mt-2 w-full rounded-lg border border-slate-300 p-2.5"></label>
                            <label class="text-sm font-semibold">Description<input data-field="description" name="criteria[{{ $index }}][description]" maxlength="500" value="{{ $criterion['description'] ?? '' }}" class="mt-2 w-full rounded-lg border border-slate-300 p-2.5"></label>
                            <button type="button" data-remove class="self-end rounded-lg px-3 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                        </div>
                    @endforeach
                </div>
                <button id="add-criterion" type="button" class="rounded-xl border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-800">+ Add question</button>
                <p class="text-sm text-slate-500">Publishing updates the form for new evaluations. Previously submitted reviews retain their original questions. Recommendations are sent to HR; HR manages permanent status separately.</p>
                <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Publish evaluation form</button>
            </form>
            </div>
            <a href="{{ route('admin.employeeEvaluation.reviews') }}" class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-900 hover:bg-emerald-100">
                <div><h2 class="font-bold">Review employee submissions</h2><p class="mt-1 text-sm">Search and review employee feedback on a separate page.</p></div>
                <span class="rounded-xl bg-white px-4 py-2 text-sm font-bold">{{ number_format($reviewCount) }} submissions <i class="fa fa-arrow-right ml-2" aria-hidden="true"></i></span>
            </a>
        </div>
    </main>
</div>
<template id="criterion-template"><div data-criterion class="grid gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[1fr_1fr_auto]"><label class="text-sm font-semibold">Question<input data-field="label" required maxlength="150" class="mt-2 w-full rounded-lg border border-slate-300 p-2.5"></label><label class="text-sm font-semibold">Description<input data-field="description" maxlength="500" class="mt-2 w-full rounded-lg border border-slate-300 p-2.5"></label><button type="button" data-remove class="self-end rounded-lg px-3 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button></div></template>
<script>
(() => {
    let list, add;
    const reindex = () => {
        list = document.getElementById('criteria-list');
        add = document.getElementById('add-criterion');
        const rows = [...list.querySelectorAll('[data-criterion]')];
        rows.forEach((row, index) => {
            row.querySelectorAll('[data-field]').forEach(input => input.name = `criteria[${index}][${input.dataset.field}]`);
            row.querySelector('[data-remove]').disabled = rows.length === 1;
        });
        add.disabled = rows.length >= 20;
    };
    document.addEventListener('click', event => {
        if (!event.target.closest('#add-criterion')) return;
        if (list.children.length >= 20) return;
        list.closest('form').dispatchEvent(new Event('input', { bubbles: true }));
        list.append(document.getElementById('criterion-template').content.cloneNode(true));
        reindex(); list.lastElementChild.querySelector('input').focus();
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('#criteria-list')) return;
        if (event.target.closest('[data-remove]') && list.children.length > 1) {
            list.closest('form').dispatchEvent(new Event('input', { bubbles: true }));
            event.target.closest('[data-criterion]').remove(); reindex();
        }
    });
    document.addEventListener('evaluation:refreshed', reindex);
    reindex();
})();
</script>
</body></html>
