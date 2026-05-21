<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Payslip View</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
    .payslip-paper {
      max-width: 980px;
      margin: 0 auto;
      background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
      border: 1px solid #dbe4f0;
      box-shadow: 0 28px 60px rgba(15, 23, 42, 0.10);
      border-radius: 2rem;
      overflow: hidden;
    }
    .section-line { border-bottom: 1px solid #dbe4f0; }
    .summary-amount {
      border-top: 1px solid #cbd5e1;
      padding-top: 0.75rem;
      margin-top: 1rem;
    }
  </style>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#eef4ff_45%,#f8fafc_100%)] text-slate-800">
@php
  $records = $records ?? collect();
  $recordItems = $records instanceof \Illuminate\Pagination\AbstractPaginator
      ? collect($records->items())
      : collect($records);
  $selectedRecord = $selectedRecord ?? null;
  $money = function ($value) {
      return is_null($value) || $value === '' ? '-' : number_format((float) $value, 2);
  };
  $employeeName = $selectedRecord?->employee_name ?: '-';
  $employeeId = $selectedRecord?->employee_id ?: '-';
  $payDateText = $selectedRecord?->pay_date ? $selectedRecord->pay_date->format('m/d/Y') : ($selectedRecord?->pay_date_text ?: '-');
  $accountCredited = $selectedRecord?->account_credited ?: '-';
  $salaryFields = [
      'basic_salary',
      'living_allowance',
      'extra_load',
      'other_income',
  ];
  $computedTotalSalary = 0.0;
  $hasSalaryValue = false;
  if ($selectedRecord) {
      foreach ($salaryFields as $field) {
          $value = $selectedRecord->{$field} ?? null;
          if ($value !== null && $value !== '') {
              $computedTotalSalary += (float) $value;
              $hasSalaryValue = true;
          }
      }
  }
  $displayTotalSalary = $hasSalaryValue ? $computedTotalSalary : null;
  $deductionFields = [
      'absences_amount',
      'withholding_tax',
      'salary_vale',
      'pag_ibig_loan',
      'pag_ibig_premium',
      'sss_loan',
      'sss_premium',
      'peraa_loan',
      'peraa_premium',
      'philhealth_premium',
      'other_deduction',
  ];
  $computedTotalDeduction = 0.0;
  $hasDeductionValue = false;
  if ($selectedRecord) {
      foreach ($deductionFields as $field) {
          $value = $selectedRecord->{$field} ?? null;
          if ($value !== null && $value !== '') {
              $computedTotalDeduction += (float) $value;
              $hasDeductionValue = true;
          }
      }
  }
  $displayTotalDeduction = $hasDeductionValue ? $computedTotalDeduction : null;
  $scannedCount = method_exists($records, 'total') ? (int) $records->total() : $recordItems->count();
@endphp

<div class="flex min-h-screen">
  @include('components.adminSideBar')

  <main class="flex-1 ml-16 transition-all duration-300">
    @include('components.adminHeader.dashboardHeader', [
      'headerTitle' => 'Payslip Review',
      'headerSubtitle' => 'Search scanned employees and inspect their payroll breakdown with a cleaner preview workspace.',
      'headerSearchPlaceholder' => 'Search employees...'
    ])

    <div class="container mx-auto max-w-7xl p-4 md:p-8 pt-10 space-y-6">
      <section class="relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] px-6 py-6 shadow-[0_24px_60px_rgba(3,19,29,0.34)] md:px-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="absolute -left-8 top-6 h-24 w-24 rounded-full bg-cyan-300/10 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-32 w-32 translate-x-10 -translate-y-8 rounded-full bg-emerald-300/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-50">
              <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
              Payroll Review Desk
            </div>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">{{ $selectedRecord ? $employeeName : 'Payslip File View' }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85 md:text-base">
              {{ $selectedRecord
                  ? 'Inspect the selected employee payroll record, verify earnings and deductions, and review the final net pay in one document-style view.'
                  : 'Select a scanned employee record from the queue to open the payroll preview.' }}
            </p>
            <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-emerald-50/80">
              <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ now()->format('l, F j, Y') }}</span>
              <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $scannedCount }} scanned record(s)</span>
              @if ($selectedRecord)
                <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">Pay Date: {{ $payDateText }}</span>
              @endif
            </div>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('admin.adminPayslip') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/8 px-5 py-3 text-sm font-semibold text-emerald-50 shadow-sm transition hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/15">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Payslip Queue
            </a>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
          <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
            <i class="fa-solid fa-id-card text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Employee ID</p>
          <p class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ $employeeId }}</p>
          <p class="mt-1 text-sm text-slate-500">Selected payroll record</p>
        </div>

        <div class="rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
          <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
            <i class="fa-solid fa-wallet text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Total Earnings</p>
          <p class="mt-2 text-2xl font-black tracking-tight text-emerald-700">{{ $money($displayTotalSalary) }}</p>
          <p class="mt-1 text-sm text-slate-500">Combined salary and income</p>
        </div>

        <div class="rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
          <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
            <i class="fa-solid fa-receipt text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Total Deductions</p>
          <p class="mt-2 text-2xl font-black tracking-tight text-rose-700">{{ $money($displayTotalDeduction) }}</p>
          <p class="mt-1 text-sm text-slate-500">Loans, taxes, and premiums</p>
        </div>

        <div class="rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur">
          <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
            <i class="fa-solid fa-money-bill-wave text-lg"></i>
          </span>
          <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Net Pay</p>
          <p class="mt-2 text-2xl font-black tracking-tight text-indigo-700">{{ $money($selectedRecord?->net_pay) }}</p>
          <p class="mt-1 text-sm text-slate-500">Final payroll amount</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.35fr)]">
        <section class="overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                Employee Queue
              </div>
              <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">Scanned employees</h2>
              <p class="mt-2 text-sm leading-6 text-slate-500">Choose a record to update the preview on the right.</p>
            </div>
            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">{{ $scannedCount }} record(s)</span>
          </div>

          <div class="mt-5">
            <label class="group flex items-center gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 transition focus-within:border-emerald-300 focus-within:bg-white focus-within:shadow-sm">
              <i class="fa-solid fa-magnifying-glass text-slate-400 transition group-focus-within:text-emerald-600"></i>
              <input
                id="employee_queue_search"
                type="text"
                placeholder="Search employee ID, name, pay date, or scanned date..."
                class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400"
              />
            </label>
          </div>

          <div class="mt-6 grid grid-cols-1 gap-4">
            @forelse ($recordItems as $record)
              @php
                $isSelected = $selectedRecord && (int) $selectedRecord->id === (int) $record->id;
                $searchName = strtolower(trim((string) ($record->employee_name ?: '')));
                $searchId = strtolower(trim((string) ($record->employee_id ?: '')));
                $recordPayDate = $record->pay_date ? $record->pay_date->format('m/d/Y') : ($record->pay_date_text ?: '-');
                $searchScannedAt = strtolower(trim((string) (optional($record->scanned_at)->format('M d, Y h:i A') ?: 'scanned')));
              @endphp
              <a
                href="{{ $isSelected ? route('admin.adminPaySlipView', ['upload_id' => ($uploadId ?? $record->payslip_upload_id)]) : route('admin.adminPaySlipView', ['upload_id' => ($uploadId ?? $record->payslip_upload_id), 'record_id' => $record->id]) }}"
                class="employee-card text-left rounded-[1.5rem] border p-5 transition {{ $isSelected ? 'border-emerald-300 bg-emerald-50/80 shadow-sm' : 'bg-white border-slate-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md' }}"
                data-employee-name="{{ $searchName }}"
                data-employee-id="{{ $searchId }}"
                data-pay-date="{{ strtolower(trim((string) $recordPayDate)) }}"
                data-scanned-at="{{ $searchScannedAt }}"
              >
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <p class="text-base font-semibold text-slate-800">{{ $record->employee_name ?: 'Employee' }}</p>
                      <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $isSelected ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $isSelected ? 'Selected' : 'Preview' }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">ID: {{ $record->employee_id ?: '-' }}</p>
                    <p class="text-sm text-slate-500">Pay Date: {{ $recordPayDate }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ optional($record->scanned_at)->format('M d, Y h:i A') ?: 'Scanned' }}</p>
                  </div>
                  <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $isSelected ? 'bg-emerald-100 text-emerald-600' : 'bg-sky-100 text-sky-600' }}">
                    <i class="fa-solid {{ $isSelected ? 'fa-check' : 'fa-eye' }}"></i>
                  </span>
                </div>
              </a>
            @empty
              <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50/70 p-8 text-center text-slate-500">
                No scanned payslip data found.
              </div>
            @endforelse
          </div>
          @if ($records instanceof \Illuminate\Pagination\AbstractPaginator && $records->hasPages())
            <div class="mt-5">
              {{ $records->links() }}
            </div>
          @endif

          <div id="employee_search_empty" class="hidden mt-4 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50/70 p-6 text-center text-sm text-slate-500">
            No employee matched your search.
          </div>
        </section>

        <section class="space-y-6">
          @if ($selectedRecord)
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-[1.5rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Payroll Date</p>
              <p class="mt-2 text-base font-semibold text-slate-800">{{ $payDateText }}</p>
            </div>
            <div class="rounded-[1.5rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Account Credited</p>
              <p class="mt-2 text-base font-semibold text-slate-800">{{ $accountCredited }}</p>
            </div>
            <div class="rounded-[1.5rem] border border-white/80 bg-white/90 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Record ID</p>
              <p class="mt-2 text-base font-semibold text-slate-800">#{{ $selectedRecord->id }}</p>
            </div>
          </div>

          <div class="overflow-hidden rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
            <div class="mb-5 flex items-center justify-between gap-4">
              <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">
                  Payroll Document
                </div>
                <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">Payslip advice preview</h2>
                <p class="mt-1 text-sm text-slate-500">Document-style summary for the selected employee payroll record.</p>
              </div>
              <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">Record #{{ $selectedRecord->id }}</span>
            </div>

            <div class="payslip-paper">
              <div class="px-8 pt-8 pb-5 text-center section-line bg-[linear-gradient(180deg,rgba(239,246,255,0.85),rgba(255,255,255,0.96))]">
                <img src="{{ asset('images/logo.png') }}" alt="Northeastern College" class="mx-auto w-[420px] max-w-full h-auto object-contain" />
                <p class="mt-4 text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">PAY SLIP / ADVICE</p>
              </div>

              <div class="px-8 py-5 section-line text-sm text-slate-800">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                  <div class="rounded-2xl bg-slate-50/80 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Pay Date</p>
                    <p class="mt-2 font-semibold text-slate-800">{{ $payDateText }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-50/80 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Employee ID</p>
                    <p class="mt-2 font-semibold text-slate-800">{{ $employeeId }}</p>
                  </div>
                  <div class="rounded-2xl bg-slate-50/80 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Account Credited</p>
                    <p class="mt-2 font-semibold text-slate-800">{{ $accountCredited }}</p>
                  </div>
                </div>
                <div class="mt-4 rounded-2xl bg-sky-50/70 px-4 py-4">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">Employee Name</p>
                  <p class="mt-2 text-lg font-semibold text-slate-900">{{ $employeeName }}</p>
                </div>
              </div>

              <div class="px-8 py-6 section-line">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
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
                    <div class="mt-6 space-y-2 text-sm text-slate-800">
                      <div class="flex justify-between"><span>Basic Salary</span><span>{{ $money($selectedRecord->basic_salary) }}</span></div>
                      <div class="flex justify-between"><span>Living Allowance</span><span>{{ $money($selectedRecord->living_allowance) }}</span></div>
                      <div class="flex justify-between"><span>Extra Load</span><span>{{ $money($selectedRecord->extra_load) }}</span></div>
                      <div class="flex justify-between"><span>Other Income</span><span>{{ $money($selectedRecord->other_income) }}</span></div>
                    </div>
                    <div class="mt-5 border-t border-emerald-100 pt-4">
                      <div class="flex items-center justify-between gap-4">
                        <div>
                          <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Earnings</p>
                          <p class="mt-1 text-sm font-medium text-slate-600">Total Salary</p>
                        </div>
                        <span class="text-2xl font-black text-slate-900">{{ $money($displayTotalSalary) }}</span>
                      </div>
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
                    <div class="mt-6 space-y-2 text-sm text-slate-800">
                      <div class="flex justify-between"><span>Absences Amount</span><span>{{ $money($selectedRecord->absences_amount) }}</span></div>
                      <div class="flex justify-between"><span>Withholding Tax</span><span>{{ $money($selectedRecord->withholding_tax) }}</span></div>
                      <div class="flex justify-between"><span>Salary Vale</span><span>{{ $money($selectedRecord->salary_vale) }}</span></div>
                      <div class="flex justify-between"><span>Pag-ibig Loan</span><span>{{ $money($selectedRecord->pag_ibig_loan) }}</span></div>
                      <div class="flex justify-between"><span>Pag-ibig Premium</span><span>{{ $money($selectedRecord->pag_ibig_premium) }}</span></div>
                      <div class="flex justify-between"><span>SSS Loan</span><span>{{ $money($selectedRecord->sss_loan) }}</span></div>
                      <div class="flex justify-between"><span>SSS Premium</span><span>{{ $money($selectedRecord->sss_premium) }}</span></div>
                      <div class="flex justify-between"><span>PERAA Loan</span><span>{{ $money($selectedRecord->peraa_loan) }}</span></div>
                      <div class="flex justify-between"><span>PERAA Premium</span><span>{{ $money($selectedRecord->peraa_premium) }}</span></div>
                      <div class="flex justify-between"><span>Philhealth Premium</span><span>{{ $money($selectedRecord->philhealth_premium) }}</span></div>
                      <div class="flex justify-between"><span>Other Deduction</span><span>{{ $money($selectedRecord->other_deduction) }}</span></div>
                    </div>
                    <div class="mt-5 border-t border-rose-100 pt-4">
                      <div class="flex items-center justify-between gap-4">
                        <div>
                          <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Deductions</p>
                          <p class="mt-1 text-sm font-medium text-slate-600">Payroll Costs</p>
                        </div>
                        <span class="text-2xl font-black text-slate-900">{{ $money($displayTotalDeduction) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-6 lg:ml-auto lg:w-[360px] rounded-[1.5rem] border border-indigo-100 bg-[linear-gradient(135deg,rgba(99,102,241,0.10),rgba(14,165,233,0.10),rgba(255,255,255,0.96))] px-5 py-4">
                  <div class="flex items-center justify-between gap-4">
                    <div>
                      <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-700">Net Pay</p>
                      <p class="mt-1 text-sm text-slate-500">Final payroll amount after deductions</p>
                    </div>
                    <span class="text-2xl font-black tracking-tight text-indigo-700">{{ $money($selectedRecord->net_pay) }}</span>
                  </div>
                </div>
              </div>

              <div class="px-8 py-5 section-line text-sm text-slate-800">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 text-left">
                  <div>
                    <p class="font-medium text-slate-700">Prepared by:</p>
                    <div class="mt-10 border-b border-slate-300"></div>
                    <p class="mt-2 text-center text-sm">ADELAIDA A. CERVANTES</p>
                  </div>
                  <div>
                    <p class="font-medium text-slate-700">Noted by:</p>
                    <div class="mt-10 border-b border-slate-300"></div>
                    <p class="mt-2 text-center text-sm">DANTE O. CLEMENTE</p>
                  </div>
                </div>
              </div>

              <div class="px-8 py-6 text-center text-sm leading-6 text-slate-700">
                I hereby acknowledge to have receive from the Treasurer of Northeastern College, Inc the sums herein specified, the same being full compensation of my services rendered during the period stated above, the correctness of which I hereby certify.
              </div>
            </div>
          </div>
          @else
          <div class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white/85 p-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
              <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
            </div>
            <h2 class="mt-5 text-xl font-black tracking-tight text-slate-900">No payslip selected</h2>
            <p class="mt-2 text-sm text-slate-500">Choose an employee record from the scanned queue to open the payroll preview here.</p>
            <a href="{{ route('admin.adminPayslip') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
              <i class="fa-solid fa-arrow-left"></i>
              Back to Payslip Queue
            </a>
          </div>
          @endif
        </section>
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

  const headerSearchInput = document.querySelector('header input[placeholder="Search employees..."]');
  const queueSearchInput = document.getElementById('employee_queue_search');
  const employeeCards = Array.from(document.querySelectorAll('.employee-card'));
  const emptySearchMessage = document.getElementById('employee_search_empty');

  if (employeeCards.length) {
    const filterCards = () => {
      const headerTerm = headerSearchInput ? headerSearchInput.value.trim().toLowerCase() : '';
      const queueTerm = queueSearchInput ? queueSearchInput.value.trim().toLowerCase() : '';
      const term = [headerTerm, queueTerm].filter(Boolean).join(' ');
      let visibleCount = 0;

      employeeCards.forEach((card) => {
        const name = (card.dataset.employeeName || '').toLowerCase();
        const id = (card.dataset.employeeId || '').toLowerCase();
        const payDate = (card.dataset.payDate || '').toLowerCase();
        const scannedAt = (card.dataset.scannedAt || '').toLowerCase();
        const haystack = `${name} ${id} ${payDate} ${scannedAt}`.trim();
        const matches = term === '' || haystack.includes(term);
        card.classList.toggle('hidden', !matches);
        if (matches) {
          visibleCount++;
        }
      });

      if (emptySearchMessage) {
        emptySearchMessage.classList.toggle('hidden', visibleCount > 0 || term === '');
      }
    };

    if (headerSearchInput) {
      headerSearchInput.addEventListener('input', filterCards);
    }
    if (queueSearchInput) {
      queueSearchInput.addEventListener('input', filterCards);
    }
  }
</script>
</body>
</html>
