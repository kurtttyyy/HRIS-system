<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Evaluation | Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>main { margin-left: 4rem; transition: margin-left .3s ease; } aside:hover ~ main { margin-left: 14rem; }</style>
</head>
<body class="bg-slate-100 text-slate-900">
<div class="flex min-h-screen">
    @include('components.employeeSideBar')
    <main class="min-w-0 flex-1">
        @include('components.employeeHeader.dashboardHeader', ['notifications' => 0, 'badge' => 'Employee Evaluation', 'subtitle' => 'Review your team using the questions published by HR.', 'status_chip' => $isDepartmentHead ? 'Department Head' : 'Employee'])
        <div class="mx-auto max-w-5xl space-y-6 p-4 pt-24 md:p-8 md:pt-24">
            <header class="rounded-3xl bg-emerald-900 p-6 text-white">
                <h1 class="text-3xl font-bold">Employee Evaluation</h1>
                <p class="mt-3 text-sm text-emerald-50">{{ $departmentName }} · Select an employee to open the evaluation questions.</p>
            </header>
            @if (session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @include('components.evaluationAutoRefresh')
            <div id="evaluation-head-region" data-evaluation-refresh class="space-y-6">
            @if (!$isDepartmentHead)
                <section class="rounded-3xl bg-white p-6">Evaluation forms are available to approved department heads.</section>
            @else
                @if (!$evaluationTemplate)
                    <section class="rounded-3xl bg-white p-6"><h2 class="text-xl font-bold">Evaluation form coming soon</h2><p class="mt-2 text-slate-500">The admin has not published evaluation questions yet.</p></section>
                @else
                    <section class="rounded-3xl bg-white p-6"><h2 class="text-xl font-bold">{{ $evaluationTemplate->title }}</h2><p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ $evaluationTemplate->instructions }}</p><p class="mt-3 text-sm text-slate-500">{{ count($evaluationTemplate->criteria) }} questions · Rate each answer from 1 (Poor) to 5 (Excellent).</p></section>
                @endif
                <h2 class="text-xl font-bold">Employees Under You</h2>
                @forelse ($evaluationEmployees as $employee)
                    @php
                        $retry = (int) old('employee_id') === $employee['id'];
                        $review = $evaluationReviews->first(fn ($item) => (int) $item->employee_user_id === (int) $employee['id']);
                    @endphp
                    <details class="rounded-3xl border border-slate-200 bg-white p-6" @if ($retry) open @endif>
                        <summary class="cursor-pointer">
                            <span class="text-lg font-bold">{{ $employee['name'] }}</span>
                            <span class="ml-2 text-sm font-semibold text-emerald-700">{{ $review ? ($review->created_at->isToday() ? 'Submitted today' : 'Submitted '.$review->created_at->format('M j, Y')) : 'Start evaluation' }}</span>
                            <span class="mt-2 block text-sm text-slate-500">{{ $employee['position'] }} · {{ $employee['employee_id'] }}</span>
                        </summary>
                        <div class="mt-5 space-y-5">
                            @if ($review)
                                <h3 class="font-bold">Submitted evaluation</h3>
                                @include('components.evaluationRatingTable', [
                                    'ratingCriteria' => $review->template->criteria,
                                    'ratingReview' => $review,
                                ])
                                @include('components.permanencyReviewDetails', ['showRatingBreakdown' => false])
                            @elseif ($evaluationTemplate)
                                <form method="POST" action="{{ route('employee.employeeEvaluation.submit', $employee['id']) }}" class="space-y-5">
                                    @csrf
                                    <input type="hidden" name="template_id" value="{{ $evaluationTemplate->id }}">
                                    <input type="hidden" name="employee_id" value="{{ $employee['id'] }}">
                                    @include('components.evaluationRatingTable', [
                                        'ratingCriteria' => ($review?->template ?? $evaluationTemplate)->criteria,
                                        'ratingReview' => $review,
                                    ])
                                    @if (!$review)
                                        <label class="block font-semibold">Recommendation<select required name="recommendation" class="mt-2 w-full rounded-xl border border-slate-300 p-3"><option value="">Select a recommendation</option>@foreach (['recommend_permanent' => 'Recommend permanent appointment', 'extend_probation' => 'Recommend extending probation', 'not_recommended' => 'Do not recommend permanent appointment'] as $value => $label)<option value="{{ $value }}" @selected($retry && old('recommendation') === $value)>{{ $label }}</option>@endforeach</select></label>
                                        <label class="block font-semibold">Comments <span class="text-sm font-normal text-slate-500">(optional)</span><textarea name="comments" maxlength="5000" rows="4" placeholder="Add feedback if you wish." class="mt-2 w-full rounded-xl border border-slate-300 p-3">{{ $retry ? old('comments') : '' }}</textarea></label>
                                        <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Submit evaluation to HR</button>
                                    @endif
                                </form>
                            @else
                                <p class="text-sm text-slate-500">Questions will appear here after the admin publishes an evaluation form.</p>
                            @endif
                        </div>
                    </details>
                @empty
                    <section class="rounded-3xl bg-white p-6">No employees are assigned under this department yet.</section>
                @endforelse
            @endif
            </div>
        </div>
    </main>
</div>
</body>
</html>
