<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permanency Evaluation | Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>main { margin-left: 4rem; transition: margin-left .3s ease; } aside:hover ~ main { margin-left: 14rem; }</style>
</head>
<body class="bg-slate-100 text-slate-900">
<div class="flex min-h-screen">
    @include('components.employeeSideBar')
    <main class="min-w-0 flex-1">
        @include('components.employeeHeader.dashboardHeader', ['notifications' => 0, 'badge' => 'Permanency Evaluation', 'subtitle' => 'Review your department’s employees for permanent appointment.', 'status_chip' => 'Department Head'])
        <div class="mx-auto max-w-5xl space-y-6 p-4 pt-24 md:p-8 md:pt-24">
            <header class="rounded-3xl bg-emerald-900 p-6 text-white">
                <h1 class="text-3xl font-bold">Permanency Evaluation</h1>
                <p class="mt-3 text-sm leading-6 text-emerald-50">Evaluate eligible probationary employees in your department and send your recommendation to HR.</p>
            </header>
            @if (session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @include('components.evaluationAutoRefresh')
            <div id="evaluation-head-region" data-evaluation-refresh class="space-y-6">
            @if (!$template)
                <section class="rounded-3xl bg-white p-8"><h2 class="text-xl font-bold">Evaluation form coming soon</h2><p class="mt-2 text-slate-500">HR has not published a permanency evaluation form yet.</p></section>
            @else
                <section class="rounded-3xl border border-slate-200 bg-white p-6"><h2 class="text-xl font-bold">{{ $template->title }}</h2><p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ $template->instructions }}</p><p class="mt-3 text-sm text-slate-500">Employees appear {{ $template->notice_days }} days before their permanency date. Ratings: 1 = Poor, 2 = Needs improvement, 3 = Satisfactory, 4 = Very good, 5 = Excellent.</p></section>
                @forelse ($employees as $employee)
                    @php
                        $due = $service->dueDate($employee);
                        $review = $reviews->first(fn ($item) => $item->employee_user_id === $employee->id && $item->due_date->toDateString() === $due->toDateString());
                        $retry = (int) old('employee_id') === (int) $employee->id;
                    @endphp
                    <details class="rounded-3xl border border-slate-200 bg-white p-6" @if ($retry) open @endif>
                        <summary class="cursor-pointer"><span class="text-lg font-bold">{{ trim($employee->first_name.' '.$employee->last_name) }}</span><span class="ml-2 text-sm font-semibold text-emerald-700">{{ $review ? 'Submitted' : 'Awaiting evaluation' }}</span><span class="mt-2 block text-sm text-slate-500">{{ $employee->employee?->position ?: $employee->position }} · Permanency {{ $due->format('M j, Y') }}{{ $due->lt(now()->startOfDay()) ? ' · Overdue' : '' }}</span></summary>
                        <div class="mt-5">
                            @if ($review)
                                @include('components.permanencyReviewDetails')
                            @else
                                <form method="POST" action="{{ route('employee.permanencyEvaluation.submit', $employee) }}" class="space-y-5">
                                    @csrf
                                    <input type="hidden" name="template_id" value="{{ $template->id }}">
                                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                    @include('components.evaluationRatingTable', [
                                        'ratingCriteria' => $template->criteria,
                                        'ratingReview' => null,
                                    ])
                                    <label class="block font-semibold">Recommendation<select required name="recommendation" class="mt-2 w-full rounded-xl border border-slate-300 p-3"><option value="">Select a recommendation</option>@foreach (['recommend_permanent' => 'Recommend permanent appointment', 'extend_probation' => 'Recommend extending probation', 'not_recommended' => 'Do not recommend permanent appointment'] as $value => $label)<option value="{{ $value }}" @selected($retry && old('recommendation') === $value)>{{ $label }}</option>@endforeach</select></label>
                                    <label class="block font-semibold">Comments and supporting observations<textarea required name="comments" maxlength="5000" rows="4" class="mt-2 w-full rounded-xl border border-slate-300 p-3">{{ $retry ? old('comments') : '' }}</textarea></label>
                                    <p class="text-sm text-slate-500">Your submitted evaluation is final and will be available to HR for review.</p>
                                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Submit to HR</button>
                                </form>
                            @endif
                        </div>
                    </details>
                @empty
                    <section class="rounded-3xl border border-slate-200 bg-white p-8"><h2 class="text-xl font-bold">No employees due for evaluation</h2><p class="mt-2 text-slate-500">Eligible probationary employees from your department will appear here as their permanency date approaches.</p></section>
                @endforelse
            @endif
            </div>
        </div>
    </main>
</div>
</body></html>
