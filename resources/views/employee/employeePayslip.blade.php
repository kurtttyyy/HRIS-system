<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslips | Employee Portal</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            transition: margin-left 0.3s ease;
        }

        main {
            transition: margin-left 0.3s ease;
        }

        aside:not(:hover) ~ main {
            margin-left: 4rem;
        }

        aside:hover ~ main {
            margin-left: 14rem;
        }

        #employee-payslip-page .employee-payslip-reveal {
            opacity: 0;
            transform: translateY(24px);
            transition:
                opacity 0.7s ease,
                transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-payslip-delay, 0ms);
        }

        #employee-payslip-page .employee-payslip-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        #employee-payslip-page .employee-payslip-card-motion {
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }

        #employee-payslip-page .employee-payslip-card-motion:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        #employee-payslip-page .employee-payslip-icon-pop {
            opacity: 0;
            transform: scale(0.86) rotate(-4deg);
            transition:
                opacity 0.55s ease,
                transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-payslip-delay, 120ms);
        }

        #employee-payslip-page .is-visible .employee-payslip-icon-pop,
        #employee-payslip-page .employee-payslip-icon-pop.is-visible {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }

        @media (prefers-reduced-motion: reduce) {
            #employee-payslip-page .employee-payslip-reveal,
            #employee-payslip-page .employee-payslip-icon-pop,
            #employee-payslip-page .employee-payslip-card-motion {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#ecfdf5,_#f8fafc_40%,_#eef2ff_100%)]">
@php
    $money = function ($value) {
        return is_null($value) || $value === '' ? 'PHP 0.00' : 'PHP '.number_format((float) $value, 2);
    };
    $moneyOrDash = function ($value) {
        return is_null($value) || $value === '' ? '-' : number_format((float) $value, 2);
    };
    $payslipCollection = collect($recentPayslips ?? []);
    $latestPayslip = $payslipCollection->first();
    $latestPayDate = $latestPayslip?->pay_date;
    $latestPeriodLabel = $latestPayDate
        ? $latestPayDate->format('F Y')
        : ($latestPayslip?->pay_date_text ?: now()->format('F Y'));
    $latestPayDateLabel = $latestPayDate
        ? $latestPayDate->format('F d, Y')
        : (optional($latestPayslip?->scanned_at)->format('F d, Y') ?: 'Not available');
    $grossPayValue = (float) ($grossSalary ?? ($latestPayslip->basic_salary ?? 0) + ($latestPayslip->living_allowance ?? 0) + ($latestPayslip->extra_load ?? 0) + ($latestPayslip->other_income ?? 0));
    $deductionValue = (float) ($deductions ?? ($latestPayslip->absences_amount ?? 0) + ($latestPayslip->withholding_tax ?? 0) + ($latestPayslip->salary_vale ?? 0) + ($latestPayslip->pag_ibig_loan ?? 0) + ($latestPayslip->pag_ibig_premium ?? 0) + ($latestPayslip->sss_loan ?? 0) + ($latestPayslip->sss_premium ?? 0) + ($latestPayslip->peraa_loan ?? 0) + ($latestPayslip->peraa_premium ?? 0) + ($latestPayslip->philhealth_premium ?? 0) + ($latestPayslip->other_deduction ?? 0));
    $netPayValue = (float) ($netSalary ?? ($latestPayslip->net_pay ?? 0));
    $otherIncomeValue = (float) ($others ?? ($latestPayslip->other_income ?? 0));
    $statusLabel = $latestPayslip ? 'Released' : 'Waiting for Payslip';
    $nextPayrollLabel = now()->copy()->endOfMonth()->format('F d, Y');
@endphp

<div class="flex min-h-screen">

 @include('components.employeeSideBar')

    <!-- MAIN CONTENT -->
    <main class="flex-1 ml-16 transition-all duration-300">
<div id="employee-payslip-page" class="p-4 md:p-8 space-y-8 pt-4">

        <section class="employee-payslip-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/40 bg-gradient-to-br from-slate-950 via-emerald-950 to-emerald-800 p-6 text-white shadow-2xl md:p-8" style="--employee-payslip-delay: 0ms;">
            <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="relative grid gap-6 xl:grid-cols-[1.7fr_1fr] xl:items-end">
                <div class="space-y-5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">
                        Payroll Center
                    </div>
                    <div>
                        <h3 class="text-3xl font-black tracking-tight md:text-5xl">{{ $money($netPayValue) }}</h3>
                        <p class="mt-2 text-base font-medium text-emerald-100">Net pay for {{ $latestPeriodLabel }}</p>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200 md:text-base">
                            Review your latest payroll breakdown, compare earnings against deductions, and open each payslip advice from one place.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="employee-payslip-card-motion employee-payslip-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-payslip-delay: 80ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Pay Period</p>
                            <p class="mt-2 text-lg font-bold">{{ $latestPeriodLabel }}</p>
                        </div>
                        <div class="employee-payslip-card-motion employee-payslip-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-payslip-delay: 120ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Status</p>
                            <p class="mt-2 text-lg font-bold">{{ $statusLabel }}</p>
                        </div>
                        <div class="employee-payslip-card-motion employee-payslip-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-payslip-delay: 160ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Pay Date</p>
                            <p class="mt-2 text-lg font-bold">{{ $latestPayDateLabel }}</p>
                        </div>
                        <div class="employee-payslip-card-motion employee-payslip-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-payslip-delay: 200ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Next Payroll</p>
                            <p class="mt-2 text-lg font-bold">{{ $nextPayrollLabel }}</p>
                        </div>
                    </div>
                </div>

                <div class="employee-payslip-card-motion employee-payslip-reveal rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur-sm" style="--employee-payslip-delay: 120ms;">
                    <div class="mb-4 flex justify-end">
                        <div class="relative group">
                            <button class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20">
                                <i class="fa fa-user"></i>
                            </button>

                            <div class="absolute right-0 z-50 mt-3 invisible w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100">
                                <a href="{{ route('employee.employeeProfile', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fa fa-user"></i>
                                    My Profile
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    @if (request()->filled('tab_session'))
                                        <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
                                    @endif
                                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50">
                                        <i class="fa fa-sign-out"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Payroll Snapshot</p>
                    <div class="mt-5 space-y-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-200">Gross Pay</span>
                            <span class="font-semibold text-white">{{ $money($grossPayValue) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-200">Deductions</span>
                            <span class="font-semibold text-white">{{ $money($deductionValue) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-200">Other Income</span>
                            <span class="font-semibold text-white">{{ $money($otherIncomeValue) }}</span>
                        </div>
                        <div class="employee-payslip-card-motion rounded-2xl bg-white/10 px-4 py-4">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Available Payslips</p>
                            <p class="mt-2 text-3xl font-black text-white">{{ $payslipCollection->count() }}</p>
                            <p class="mt-1 text-xs text-slate-200">Use the list below to open older payroll records.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <article class="employee-payslip-card-motion employee-payslip-reveal rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm" style="--employee-payslip-delay: 120ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-payslip-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20" style="--employee-payslip-delay: 180ms;">
                        <i class="fa-solid fa-wallet text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Earnings</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $money($grossPayValue) }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Gross Pay</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Base compensation and payroll additions before mandatory deductions are removed.</p>
            </article>

            <article class="employee-payslip-card-motion employee-payslip-reveal rounded-[1.75rem] border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-6 shadow-sm" style="--employee-payslip-delay: 160ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-payslip-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/20" style="--employee-payslip-delay: 220ms;">
                        <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Payroll Costs</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $money($deductionValue) }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Deductions</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Includes taxes, loans, premiums, and any other payroll reductions for the selected payslip.</p>
            </article>

            <article class="employee-payslip-card-motion employee-payslip-reveal rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm" style="--employee-payslip-delay: 200ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-payslip-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20" style="--employee-payslip-delay: 260ms;">
                        <i class="fa-solid fa-money-bill-wave text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $statusLabel }}</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $money($netPayValue) }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Net Pay</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Take-home amount after payroll deductions have been applied to the current release.</p>
            </article>

            <article class="employee-payslip-card-motion employee-payslip-reveal rounded-[1.75rem] border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm" style="--employee-payslip-delay: 240ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-payslip-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20" style="--employee-payslip-delay: 300ms;">
                        <i class="fa-solid fa-coins text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Adjustments</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $money($otherIncomeValue) }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Other Income</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Captures additional pay items included alongside the standard salary components.</p>
            </article>
        </section>

        <section id="payslip-history-section" class="employee-payslip-reveal rounded-[2rem] border border-slate-200 bg-white shadow-sm" style="--employee-payslip-delay: 280ms;">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Payslip History</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">Recent Payslips</h3>
                    <p class="mt-1 text-sm text-slate-500">Open any payslip below to review the full payroll advice and detailed breakdown.</p>
                </div>
                <div class="employee-payslip-card-motion rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-4 py-3 text-sm">
                    <p class="text-xs uppercase tracking-wide text-emerald-700">Latest Release</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $latestPayDateLabel }}</p>
                </div>
            </div>

            <div class="p-6 space-y-4">
                @forelse(($recentPayslips ?? collect()) as $payslip)
                    @php
                        $periodLabel = $payslip->pay_date
                            ? $payslip->pay_date->format('F Y')
                            : ($payslip->pay_date_text ?: 'Payslip');
                        $paidOnLabel = $payslip->pay_date
                            ? $payslip->pay_date->format('M d, Y')
                            : (optional($payslip->scanned_at)->format('M d, Y') ?: '-');
                        $totalSalary = (float) ($payslip->basic_salary ?? 0)
                            + (float) ($payslip->living_allowance ?? 0)
                            + (float) ($payslip->extra_load ?? 0)
                            + (float) ($payslip->other_income ?? 0);
                        $totalDeduction = (float) ($payslip->absences_amount ?? 0)
                            + (float) ($payslip->withholding_tax ?? 0)
                            + (float) ($payslip->salary_vale ?? 0)
                            + (float) ($payslip->pag_ibig_loan ?? 0)
                            + (float) ($payslip->pag_ibig_premium ?? 0)
                            + (float) ($payslip->sss_loan ?? 0)
                            + (float) ($payslip->sss_premium ?? 0)
                            + (float) ($payslip->peraa_loan ?? 0)
                            + (float) ($payslip->peraa_premium ?? 0)
                            + (float) ($payslip->philhealth_premium ?? 0)
                            + (float) ($payslip->other_deduction ?? 0);
                    @endphp
                    <div class="employee-payslip-card-motion rounded-[1.5rem] border border-emerald-100 bg-gradient-to-r from-white to-emerald-50/70 p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-start gap-4">
                                <div class="employee-payslip-icon-pop is-visible flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20">
                                    <i class="fa-solid fa-receipt text-xl"></i>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <p class="text-lg font-bold text-slate-900">{{ $periodLabel }}</p>
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Released</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">Paid on {{ $paidOnLabel }}</p>
                                    <p class="mt-3 text-xs uppercase tracking-wide text-slate-400">Gross: {{ $money($totalSalary) }} | Deductions: {{ $money($totalDeduction) }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-3 text-left lg:items-end lg:text-right">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Net Pay</p>
                                    <p class="mt-1 text-2xl font-black text-slate-900">{{ $money($payslip->net_pay) }}</p>
                                </div>
                                <button
                                    type="button"
                                    data-open-modal="payslip-modal-{{ $payslip->id }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    View Payslip
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="payslip-modal-{{ $payslip->id }}" class="hidden fixed inset-0 z-50">
                        <div class="absolute inset-0 bg-black/50" data-close-modal></div>
                        <div class="relative z-10 flex min-h-screen items-center justify-center p-4">
                            <div class="w-full max-w-5xl overflow-y-auto rounded-[2rem] border border-slate-200 bg-white shadow-2xl max-h-[90vh]" data-payslip-scroll-panel>
                                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Payslip Advice</p>
                                        <h4 class="mt-1 text-xl font-bold text-slate-900">{{ $periodLabel }}</h4>
                                    </div>
                                    <button type="button" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm text-emerald-700 hover:bg-emerald-50" data-close-modal>
                                        Close
                                    </button>
                                </div>

                                <div class="border-b border-slate-200 px-8 py-5 text-center">
                                    <img src="{{ asset('images/logo.png') }}" alt="Northeastern College" class="mx-auto w-[380px] max-w-full h-auto object-contain" />
                                </div>

                                <div class="border-b border-slate-200 px-8 py-4 text-center">
                                    <p class="font-semibold tracking-wide text-slate-900">PAY SLIP / ADVICE</p>
                                </div>

                                <div class="border-b border-slate-200 px-8 py-5 text-sm text-gray-800">
                                    <p><span class="font-medium">Pay Date:</span> {{ $payslip->pay_date ? $payslip->pay_date->format('m/d/Y') : ($payslip->pay_date_text ?: '-') }}</p>
                                    <div class="mt-2 flex flex-wrap gap-x-8 gap-y-2">
                                        <p><span class="font-medium">Emp ID No:</span> {{ $payslip->employee_id ?: '-' }}</p>
                                        <p><span class="font-medium">Acct #:</span> {{ $payslip->account_credited ?: '-' }}</p>
                                    </div>
                                    <p class="mt-2"><span class="font-medium">Emp Name:</span> {{ $payslip->employee_name ?: '-' }}</p>
                                </div>

                                <div class="border-b border-slate-200 px-8 py-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm text-gray-800">
                                        <div class="rounded-[1.75rem] border border-emerald-200 bg-[linear-gradient(180deg,rgba(236,253,245,0.92),rgba(255,255,255,0.98))] p-6">
                                            <div class="flex items-start gap-4">
                                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                                                    <i class="fa-solid fa-arrow-trend-up text-2xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">Earnings</p>
                                                    <p class="mt-1 text-sm leading-6 text-slate-500">Salary and additional income</p>
                                                </div>
                                            </div>
                                            <div class="mt-6 space-y-1.5">
                                                <div class="flex justify-between"><span>Basic Salary</span><span>{{ $moneyOrDash($payslip->basic_salary) }}</span></div>
                                                <div class="flex justify-between"><span>Living Allowance</span><span>{{ $moneyOrDash($payslip->living_allowance) }}</span></div>
                                                <div class="flex justify-between"><span>Extra Load</span><span>{{ $moneyOrDash($payslip->extra_load) }}</span></div>
                                                <div class="flex justify-between"><span>Other Income</span><span>{{ $moneyOrDash($payslip->other_income) }}</span></div>
                                            </div>
                                            <div class="mt-5 border-t border-emerald-100 pt-4 flex justify-between items-center gap-4">
                                                <div>
                                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Earnings</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-600">Total Salary</p>
                                                </div>
                                                <span class="text-2xl font-black text-slate-900">{{ number_format($totalSalary, 2) }}</span>
                                            </div>
                                        </div>

                                        <div class="rounded-[1.75rem] border border-rose-200 bg-[linear-gradient(180deg,rgba(255,241,242,0.92),rgba(255,255,255,0.98))] p-6">
                                            <div class="flex items-start gap-4">
                                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                                                    <i class="fa-solid fa-arrow-trend-down text-2xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-700">Deductions</p>
                                                    <p class="mt-1 text-sm leading-6 text-slate-500">Taxes, loans, and contribution amounts</p>
                                                </div>
                                            </div>
                                            <div class="mt-6 space-y-1.5">
                                                <div class="flex justify-between"><span>Absences Amount</span><span>{{ $moneyOrDash($payslip->absences_amount) }}</span></div>
                                                <div class="flex justify-between"><span>Withholding Tax</span><span>{{ $moneyOrDash($payslip->withholding_tax) }}</span></div>
                                                <div class="flex justify-between"><span>Salary Vale</span><span>{{ $moneyOrDash($payslip->salary_vale) }}</span></div>
                                                <div class="flex justify-between"><span>Pag-ibig Loan</span><span>{{ $moneyOrDash($payslip->pag_ibig_loan) }}</span></div>
                                                <div class="flex justify-between"><span>Pag-ibig Premium</span><span>{{ $moneyOrDash($payslip->pag_ibig_premium) }}</span></div>
                                                <div class="flex justify-between"><span>SSS Loan</span><span>{{ $moneyOrDash($payslip->sss_loan) }}</span></div>
                                                <div class="flex justify-between"><span>SSS Premium</span><span>{{ $moneyOrDash($payslip->sss_premium) }}</span></div>
                                                <div class="flex justify-between"><span>PERAA Loan</span><span>{{ $moneyOrDash($payslip->peraa_loan) }}</span></div>
                                                <div class="flex justify-between"><span>PERAA Premium</span><span>{{ $moneyOrDash($payslip->peraa_premium) }}</span></div>
                                                <div class="flex justify-between"><span>Philhealth Premium</span><span>{{ $moneyOrDash($payslip->philhealth_premium) }}</span></div>
                                                <div class="flex justify-between"><span>Other Deduction</span><span>{{ $moneyOrDash($payslip->other_deduction) }}</span></div>
                                            </div>
                                            <div class="mt-5 border-t border-rose-100 pt-4 flex justify-between items-center gap-4">
                                                <div>
                                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Deductions</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-600">Payroll Costs</p>
                                                </div>
                                                <span class="text-2xl font-black text-slate-900">{{ number_format($totalDeduction, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5 rounded-2xl bg-emerald-50 px-5 py-4 md:ml-auto md:w-1/2 border border-emerald-200 flex justify-between text-base font-bold text-emerald-900">
                                        <span>Net Pay</span>
                                        <span>{{ number_format((float) ($payslip->net_pay ?? 0), 2) }}</span>
                                    </div>
                                </div>

                                <div class="border-b border-slate-200 px-8 py-5 text-sm text-gray-800">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-left">
                                        <div>
                                            <p class="font-medium">Prepared by:</p>
                                            <div class="mt-10"></div>
                                            <p class="mt-2 text-sm text-center">ADELAIDA A. CERVANTES</p>
                                        </div>
                                        <div>
                                            <p class="font-medium">Noted by:</p>
                                            <div class="mt-10"></div>
                                            <p class="mt-2 text-sm text-center">DANTE O. CLEMENTE</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-8 py-5 text-center text-sm text-gray-800">
                                    I hereby acknowledge to have receive from the Treasurer of Northeastern College, Inc the sums herein specified, the same being full compensation of my services rendered during the period stated above, the correctness of which I hereby certify.
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="employee-payslip-card-motion flex flex-col items-center justify-center rounded-[1.5rem] border border-dashed border-emerald-200 bg-emerald-50/60 px-6 py-14 text-center">
                        <div class="employee-payslip-icon-pop is-visible flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                            <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
                        </div>
                        <h4 class="mt-5 text-xl font-bold text-slate-900">No payslip data found yet</h4>
                        <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">Your payroll advice will appear here after the admin scans and releases your payslip file.</p>
                    </div>
                @endforelse
            </div>
        </section>

    </div>

    </main>

</div>

<script>
    const initEmployeePayslipAnimation = () => {
        const page = document.getElementById('employee-payslip-page');
        if (!page) return;

        const animatedItems = page.querySelectorAll('.employee-payslip-reveal');

        if (!('IntersectionObserver' in window)) {
            animatedItems.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.14,
            rootMargin: '0px 0px -40px 0px',
        });

        animatedItems.forEach((item) => observer.observe(item));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeePayslipAnimation, { once: true });
    } else {
        initEmployeePayslipAnimation();
    }

    const sidebar = document.querySelector('aside');
    const main = document.querySelector('main');

    (function () {
        const focusId = @json(request()->query('focus'));
        if (!focusId) return;
        const target = document.getElementById(focusId);
        if (!target) return;

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('ring-4', 'ring-emerald-300', 'ring-offset-4', 'ring-offset-slate-100', 'transition');

        setTimeout(() => {
            target.classList.remove('ring-4', 'ring-emerald-300', 'ring-offset-4', 'ring-offset-slate-100');
        }, 2200);
    })();

    if (sidebar && main) {
        sidebar.addEventListener('mouseenter', function() {
            main.classList.remove('ml-16');
            main.classList.add('ml-56');
        });

        sidebar.addEventListener('mouseleave', function() {
            main.classList.remove('ml-56');
            main.classList.add('ml-16');
        });
    }

    const closePayslipModal = (modal) => {
        if (!modal) return;

        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const modalOpenButtons = document.querySelectorAll('[data-open-modal]');
    modalOpenButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const modalId = this.getAttribute('data-open-modal');
            const modal = modalId ? document.getElementById(modalId) : null;
            if (!modal) return;

            document.body.appendChild(modal);
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            const scrollPanel = modal.querySelector('[data-payslip-scroll-panel]');
            if (scrollPanel) {
                scrollPanel.scrollTop = 0;
            }
        });
    });

    document.addEventListener('click', function (event) {
        const closeTrigger = event.target.closest('[data-close-modal]');
        if (!closeTrigger) return;

        closePayslipModal(closeTrigger.closest('[id^="payslip-modal-"]'));
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        document
            .querySelectorAll('[id^="payslip-modal-"]:not(.hidden)')
            .forEach(closePayslipModal);
    });
</script>

</body>
</html>



