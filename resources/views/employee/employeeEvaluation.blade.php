<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Evaluation - Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { transition: margin-left 0.3s ease; }
        main { transition: margin-left 0.3s ease; }
        aside:not(:hover) ~ main { margin-left: 4rem; }
        aside:hover ~ main { margin-left: 14rem; }
    </style>
</head>
<body class="bg-slate-100">

<div class="flex min-h-screen">
    @include('components.employeeSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        @include('components.employeeHeader.dashboardHeader', [
            'notifications' => (int) ($notifications ?? 0),
            'badge' => 'Employee Evaluation',
            'subtitle' => 'Track performance reviews, scores, and manager feedback in one focused workspace.',
            'status_chip' => 'Evaluation Mode',
        ])

        <div class="space-y-6 p-4 pt-20 md:p-8">
            <section class="overflow-hidden rounded-[1.4rem] bg-gradient-to-r from-emerald-800 via-emerald-700 to-emerald-600 px-6 py-5 text-white shadow-[0_24px_60px_rgba(5,150,105,0.22)]">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-100/90">Employee Performance Portal</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight">Employee Evaluation System</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/90">
                            A clean HR web interface for evaluating employee performance, tracking scores, and saving manager feedback.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="min-w-[150px] rounded-2xl bg-white/12 px-4 py-3 ring-1 ring-white/12 backdrop-blur">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100/80">Evaluation Period</p>
                            <p class="mt-2 text-sm font-semibold text-white">Q1 2026</p>
                        </div>
                        <div class="min-w-[150px] rounded-2xl bg-white/12 px-4 py-3 ring-1 ring-white/12 backdrop-blur">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100/80">Overall Rating</p>
                            <p class="mt-2 text-sm font-semibold text-white">4.3 / 5.0</p>
                        </div>
                        @if (!empty($isDepartmentHead))
                            <div class="min-w-[150px] rounded-2xl bg-white/12 px-4 py-3 ring-1 ring-white/12 backdrop-blur sm:col-span-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100/80">Employees Under You</p>
                                <p class="mt-2 text-sm font-semibold text-white">{{ $evaluationEmployees->count() }} {{ Str::plural('employee', $evaluationEmployees->count()) }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_380px]">
                <div class="space-y-5">
                    <section class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Employee Information</h2>
                                <p class="mt-1 text-sm text-slate-500">Basic details shown before the evaluation form.</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active Review</span>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Employee Name</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">
                                    {{ trim(collect([$user->first_name ?? null, $user->middle_name ?? null, $user->last_name ?? null])->filter()->implode(' ')) ?: 'Juan Dela Cruz' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Department</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $departmentName ?? $user->department ?? $user->employee?->department ?? 'Human Resources' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Position</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->position ?? $user->employee?->position ?? 'HR Assistant' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ !empty($isDepartmentHead) ? 'Review Scope' : 'Immediate Supervisor' }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ !empty($isDepartmentHead) ? (($departmentName ?? 'Department').' Team') : 'Maria Santos' }}</p>
                            </div>
                        </div>
                    </section>

                    @if (!empty($isDepartmentHead))
                        <section class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Employees Under You</h2>
                                    <p class="mt-1 text-sm text-slate-500">Department employees available for hierarchy and evaluation review.</p>
                                </div>
                                <a href="{{ route('employee.employeeHierarchy') }}" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                    View Hierarchy
                                </a>
                            </div>

                            @if ($evaluationEmployees->isNotEmpty())
                                <div class="mt-5 grid gap-3 md:grid-cols-2">
                                    @foreach ($evaluationEmployees as $employee)
                                        <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <h3 class="text-sm font-bold text-slate-900">{{ $employee['name'] }}</h3>
                                                    <p class="mt-1 text-xs font-semibold text-emerald-700">{{ $employee['position'] }}</p>
                                                </div>
                                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">{{ $employee['employee_id'] }}</span>
                                            </div>
                                            <div class="mt-3 grid gap-2 text-xs text-slate-500">
                                                <p>{{ $employee['email'] }}</p>
                                                <p>{{ $employee['status'] }}</p>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-5 rounded-2xl border border-dashed border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                                    No employees are assigned under this department yet.
                                </div>
                            @endif
                        </section>
                    @endif

                    <section class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Evaluation Criteria</h2>
                                <p class="mt-1 text-sm text-slate-500">Managers score each category from 1 to 5.</p>
                            </div>
                            <button type="button" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                Save Draft
                            </button>
                        </div>

                        @php
                            $criteria = [
                                ['title' => 'Work Quality', 'description' => 'Accuracy, completeness, attention to detail', 'score' => '4.5 / 5', 'width' => '89%'],
                                ['title' => 'Attendance & Punctuality', 'description' => 'On time record and reliability', 'score' => '4.2 / 5', 'width' => '84%'],
                                ['title' => 'Communication', 'description' => 'Clear coordination with team and clients', 'score' => '4.0 / 5', 'width' => '80%'],
                                ['title' => 'Teamwork', 'description' => 'Supports colleagues and collaborates well', 'score' => '4.6 / 5', 'width' => '91%'],
                                ['title' => 'Initiative', 'description' => 'Problem-solving and ownership', 'score' => '4.1 / 5', 'width' => '82%'],
                                ['title' => 'Productivity', 'description' => 'Output, speed, consistency', 'score' => '4.3 / 5', 'width' => '86%'],
                            ];
                        @endphp

                        <div class="mt-5 space-y-3">
                            @foreach ($criteria as $criterion)
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-900">{{ $criterion['title'] }}</h3>
                                            <p class="mt-1 text-xs text-slate-500">{{ $criterion['description'] }}</p>
                                        </div>
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $criterion['score'] }}</span>
                                    </div>
                                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-emerald-600" style="width: {{ $criterion['width'] }}"></div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                        <h2 class="text-lg font-bold text-slate-900">Manager Feedback</h2>

                        <textarea
                            rows="4"
                            class="mt-4 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                        >Shows strong teamwork and consistently completes assigned tasks on time. Can further improve in written communication and reporting format.</textarea>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <select class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                                <option>Recommended Training: Advanced Excel</option>
                                <option>Recommended Training: Leadership Workshop</option>
                                <option>Recommended Training: Communication Skills</option>
                            </select>
                            <select class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                                <option>Promotable</option>
                                <option>Needs Coaching</option>
                                <option>For Improvement Plan</option>
                            </select>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <button type="button" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Submit Evaluation
                            </button>
                            <button type="button" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                                Print PDF
                            </button>
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                        <h2 class="text-lg font-bold text-slate-900">Rating Guide</h2>
                        @php
                            $ratingGuide = [
                                ['score' => '5 - Outstanding', 'description' => 'Consistently exceeds expectations'],
                                ['score' => '4 - Very Good', 'description' => 'Usually exceeds expectations'],
                                ['score' => '3 - Satisfactory', 'description' => 'Meets normal expectations'],
                                ['score' => '2 - Needs Improvement', 'description' => 'Sometimes below expectations'],
                                ['score' => '1 - Poor', 'description' => 'Frequently below standard'],
                            ];
                        @endphp

                        <div class="mt-4 space-y-3">
                            @foreach ($ratingGuide as $item)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $item['score'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                        <h2 class="text-lg font-bold text-slate-900">Design Sections</h2>
                        @php
                            $sections = [
                                'Dashboard Header: period, employee name, overall score.',
                                'Employee Profile: department, position, supervisor, review status.',
                                'Evaluation Form: rating categories with sliders, radio buttons, or dropdowns.',
                                'Feedback Box: strengths, weaknesses, development plan.',
                                'Result Actions: save draft, submit, export, print.',
                            ];
                        @endphp

                        <div class="mt-4 space-y-3">
                            @foreach ($sections as $index => $section)
                                <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm text-slate-600">
                                    <span class="font-semibold text-slate-900">{{ $index + 1 }}.</span> {{ $section }}
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-[1.6rem] border border-dashed border-emerald-300 bg-emerald-50/70 p-5 shadow-[0_18px_45px_rgba(15,23,42,0.04)]">
                        <h2 class="text-lg font-bold text-emerald-900">Suggested Features</h2>
                        <ul class="mt-4 space-y-2 text-sm text-emerald-800">
                            <li>. Self evaluation and manager evaluation</li>
                            <li>. Auto-compute average score</li>
                            <li>. Evaluation history per employee</li>
                            <li>. PDF export and printable report</li>
                            <li>. Approval workflow from HR / department head</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </main>
</div>

</body>
</html>
