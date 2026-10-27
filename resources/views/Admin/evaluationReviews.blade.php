<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Evaluations | Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @include('components.evaluationPageMotion')
</head>
<body class="bg-slate-100 text-slate-900">
<div class="flex min-h-screen">
    @include('components.adminSideBar')
    <main data-admin-sidebar-layout class="min-w-0 flex-1 p-4 pt-20 md:p-8 md:pt-20 lg:pt-8">
        <div class="evaluation-page-shell mx-auto max-w-6xl space-y-6">
            <header class="rounded-3xl bg-emerald-900 p-6 text-white md:p-8">
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-100">Employee development</p>
                <h1 class="mt-2 text-3xl font-bold">Submitted Evaluations</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-emerald-50">Find employee evaluations and review ratings, recommendations, and feedback from department heads.</p>
            </header>
            <nav aria-label="Evaluation pages" class="evaluation-tabs flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2">
                <a href="{{ route('admin.employeeEvaluation.customize') }}"  class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Create evaluation</a>
                <a href="{{ route('admin.employeeEvaluation.reviews') }}" aria-current="page" class="rounded-xl px-4 py-3 text-sm font-semibold bg-emerald-700 text-white"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>Submitted evaluations</a>
            </nav>
            @include('components.evaluationAutoRefresh')
            <section id="evaluation-reviews-region" data-evaluation-refresh class="rounded-3xl border border-slate-200 bg-white p-6">
                <h2 class="text-xl font-bold">Submitted evaluations</h2>
                <p class="mt-1 text-sm text-slate-500">Review recommendations and feedback from department heads.</p>
                <form method="GET" action="{{ route('admin.employeeEvaluation.reviews') }}" class="mt-5 grid gap-3 md:grid-cols-[1fr_1fr_auto_auto]">
                    <label class="text-xs font-semibold text-slate-600">Employee name<input type="search" name="search" maxlength="150" value="{{ $search }}" placeholder="Search employees…" class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm"></label>
                    <label class="text-xs font-semibold text-slate-600">Recommendation<select name="recommendation" class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm">
                        <option value="">All recommendations</option>
                        @foreach (['recommend_permanent' => 'Recommend permanent', 'extend_probation' => 'Extend probation', 'not_recommended' => 'Not recommended'] as $value => $label)
                            <option value="{{ $value }}" @selected($recommendation === $value)>{{ $label }}</option>
                        @endforeach
                    </select></label>
                    <button class="self-end rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Search</button>
                    <a href="{{ route('admin.employeeEvaluation.reviews') }}" class="self-end rounded-xl border border-slate-200 px-5 py-3 text-center text-sm font-semibold">Reset</a>
                </form>
                <p class="mt-4 text-xs text-slate-500">{{ number_format($reviews->total()) }} matching submissions · 15 per page · Newest first</p>
                <div class="mt-5 space-y-3">
                    @forelse ($reviews as $review)
                        <details class="rounded-2xl border border-slate-200 p-4">
                            <summary class="cursor-pointer font-semibold">{{ trim(($review->employeeUser?->first_name ?? '').' '.($review->employeeUser?->last_name ?? '')) ?: 'Employee #'.$review->employee_user_id }} <span class="ml-2 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">Rating: {{ $review->overall_rating }}/5</span></summary>
                            <p class="my-3 text-sm text-slate-500">Reviewed by {{ trim(($review->headUser?->first_name ?? '').' '.($review->headUser?->last_name ?? '')) ?: 'Head #'.$review->head_user_id }}</p>
                            @include('components.permanencyReviewDetails')
                        </details>
                    @empty<p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-500">No evaluations found. Try another employee name or reset the filters.</p>@endforelse
                </div>
                <div class="mt-5">{{ $reviews->links() }}</div>
            </section>
        </div>
    </main>
</div>
</body></html>
