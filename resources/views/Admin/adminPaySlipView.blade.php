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
    .payslip-employee-list {
      max-height: 640px;
      overflow-y: auto;
      overscroll-behavior: contain;
      scrollbar-gutter: stable;
      padding-right: 0.4rem;
    }
    .payslip-employee-list::-webkit-scrollbar { width: 8px; }
    .payslip-employee-list::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 9999px;
    }
    .payslip-employee-list::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 9999px;
    }
    .payslip-employee-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    @media screen and (max-width: 767px) {
      main {
        min-width: 0;
      }
      main > .container {
        width: 100%;
        padding: 0.5rem 0.75rem 1rem !important;
      }
      #payslip-review-hero {
        padding: 1rem !important;
        border-radius: 1.25rem !important;
      }
      #payslip-review-hero h1 {
        margin-top: 0.75rem;
        font-size: 1.5rem;
        line-height: 1.2;
      }
      #payslip-review-hero p {
        line-height: 1.5;
      }
      #payslip-review-hero .relative.flex {
        gap: 1rem;
      }
      #payslip-review-hero .relative.flex > div:first-child > div:first-child {
        max-width: calc(100% - 3rem);
        margin-left: 3rem;
        padding-left: 0.65rem;
        padding-right: 0.65rem;
        font-size: 0.6rem;
        letter-spacing: 0.14em;
      }
      #payslip-review-hero .mt-4.flex.flex-wrap {
        gap: 0.5rem;
      }
      #payslip-review-hero .mt-4.flex.flex-wrap > span {
        padding: 0.35rem 0.65rem;
        font-size: 0.68rem;
      }
      #payslip-review-hero a {
        width: 100%;
        padding: 0.7rem 1rem;
      }
      #payslip-review-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
      }
      #payslip-review-summary > div {
        min-width: 0;
        padding: 0.875rem;
        border-radius: 1rem;
      }
      #payslip-review-summary > div > span {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.75rem;
      }
      #payslip-review-summary p.mt-4 {
        margin-top: 0.75rem;
        font-size: 0.65rem;
        letter-spacing: 0.08em;
      }
      #payslip-review-summary p.text-2xl {
        overflow-wrap: anywhere;
        font-size: 1.25rem;
      }
      #payslip-review-queue {
        padding: 1rem;
        border-radius: 1.25rem;
      }
      #payslip-review-queue .payslip-employee-list {
        max-height: 34rem;
        padding-right: 0.15rem;
      }
      #payslip-selected-meta {
        grid-template-areas: "date record" "account account";
        gap: 0.5rem;
      }
      #payslip-review-preview > * + * {
        margin-top: 0.75rem;
      }
      #payslip-selected-meta > div {
        padding: 0.75rem;
        border-radius: 0.875rem;
        box-shadow: none;
      }
      #payslip-selected-meta > div:nth-child(1) { grid-area: date; }
      #payslip-selected-meta > div:nth-child(2) { grid-area: account; }
      #payslip-selected-meta > div:nth-child(3) { grid-area: record; }
      #payslip-selected-meta p:first-child {
        font-size: 0.58rem;
        letter-spacing: 0.1em;
      }
      #payslip-selected-meta p:last-child {
        margin-top: 0.25rem;
        overflow-wrap: anywhere;
        font-size: 0.85rem;
      }
      #payslip-document-panel {
        padding: 1rem;
        border-radius: 1.25rem;
        box-shadow: none;
      }
      #payslip-document-heading {
        margin-bottom: 0.875rem;
      }
      #payslip-document-heading h2 {
        margin-top: 0.5rem;
        font-size: 1.25rem;
        line-height: 1.25;
      }
      #payslip-document-heading p {
        font-size: 0.75rem;
        line-height: 1.4;
      }
      #payslip-document-heading > span {
        display: none;
      }
      .payslip-paper { border-radius: 1rem; box-shadow: none; }
      .payslip-paper > div:first-child { padding: 1rem 0.75rem 0.75rem; }
      .payslip-paper > div:first-child img { width: 15rem; }
      .payslip-paper > div:first-child p { margin-top: 0.5rem; font-size: 0.65rem; letter-spacing: 0.16em; }
      .payslip-paper > div:nth-child(2) { padding: 0.75rem; }
      .payslip-paper > div:nth-child(2) > .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem; }
      .payslip-paper > div:nth-child(2) > .grid > div { padding: 0.6rem; border-radius: 0.75rem; }
      .payslip-paper > div:nth-child(2) > .grid > div:nth-child(3) { grid-column: 1 / -1; }
      .payslip-paper > div:nth-child(2) p { overflow-wrap: anywhere; }
      .payslip-paper > div:nth-child(2) .mt-4 { margin-top: 0.5rem; padding: 0.65rem; border-radius: 0.75rem; }
      .payslip-paper > div:nth-child(2) .text-lg { margin-top: 0.25rem; font-size: 0.9rem; }
      .payslip-paper > div:nth-child(3) { padding: 0.75rem; }
      .payslip-paper > div:nth-child(3) > .grid { gap: 0.75rem; }
      .payslip-paper .border-emerald-200,
      .payslip-paper .border-rose-200 { padding: 0.875rem; border-radius: 1rem; }
      .payslip-paper .border-emerald-200 > .flex,
      .payslip-paper .border-rose-200 > .flex { gap: 0.65rem; }
      .payslip-paper .border-emerald-200 > .flex > div:first-child,
      .payslip-paper .border-rose-200 > .flex > div:first-child { width: 2.25rem; height: 2.25rem; border-radius: 0.75rem; }
      .payslip-paper .border-emerald-200 .mt-6,
      .payslip-paper .border-rose-200 .mt-6 { margin-top: 0.75rem; }
      .payslip-paper .border-emerald-200 .space-y-2,
      .payslip-paper .border-rose-200 .space-y-2 { font-size: 0.75rem; }
      .payslip-paper .border-emerald-200 .space-y-2 > div,
      .payslip-paper .border-rose-200 .space-y-2 > div { gap: 0.75rem; }
      .payslip-paper .payslip-empty-line { display: none; }
      .payslip-paper .border-emerald-200 .mt-5,
      .payslip-paper .border-rose-200 .mt-5 { margin-top: 0.75rem; padding-top: 0.75rem; }
      .payslip-paper .border-emerald-200 .text-2xl,
      .payslip-paper .border-rose-200 .text-2xl { font-size: 1.25rem; }
      #payslip-net-pay {
        width: 100%;
        margin-top: 0.75rem;
        padding: 0.75rem;
        border-radius: 0.875rem;
        background: #f5f7ff;
      }
      #payslip-net-pay p:last-child {
        max-width: 8rem;
        font-size: 0.68rem;
        line-height: 1.35;
      }
      #payslip-net-pay span {
        font-size: 1.15rem;
      }
      #payslip-signatures {
        padding: 0.75rem;
      }
      #payslip-signatures > .grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
      }
      #payslip-signatures .mt-10 {
        margin-top: 1.25rem;
      }
      #payslip-signatures p {
        font-size: 0.65rem;
        line-height: 1.35;
      }
      #payslip-signatures p.mt-2 {
        margin-top: 0.35rem;
        overflow-wrap: anywhere;
      }
      #payslip-acknowledgement {
        padding: 0.75rem;
        text-align: left;
        font-size: 0.65rem;
        line-height: 1.45;
        color: #64748b;
      }
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
  $isBlankAmount = fn ($value) => is_null($value) || $value === '';
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

    <div class="container mx-auto max-w-7xl space-y-4 px-3 pb-4 pt-2 md:space-y-6 md:p-8">
      <section id="payslip-review-hero" class="relative overflow-hidden rounded-[1.25rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] px-4 py-4 shadow-[0_24px_60px_rgba(3,19,29,0.34)] md:rounded-[2rem] md:px-8 md:py-6">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="absolute -left-8 top-6 h-24 w-24 rounded-full bg-cyan-300/10 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-32 w-32 translate-x-10 -translate-y-8 rounded-full bg-emerald-300/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-50">
              <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
              Payroll Review Desk
            </div>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-white md:mt-4 md:text-4xl">{{ $selectedRecord ? $employeeName : 'Payslip File View' }}</h1>
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

      <div id="payslip-review-summary" class="grid grid-cols-2 gap-3 md:grid-cols-2 md:gap-4 xl:grid-cols-4">
        <div class="min-w-0 rounded-2xl border border-white/80 bg-white/90 p-3 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur md:rounded-[1.75rem] md:p-5">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sky-600 md:h-12 md:w-12 md:rounded-2xl">
            <i class="fa-solid fa-id-card text-lg"></i>
          </span>
          <p class="mt-3 text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400 md:mt-4 md:text-xs md:tracking-[0.18em]">Employee ID</p>
          <p class="mt-1 text-xl font-black tracking-tight text-slate-900 md:mt-2 md:text-2xl">{{ $employeeId }}</p>
          <p class="mt-1 text-sm text-slate-500">Selected payroll record</p>
        </div>

        <div class="min-w-0 rounded-2xl border border-white/80 bg-white/90 p-3 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur md:rounded-[1.75rem] md:p-5">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 md:h-12 md:w-12 md:rounded-2xl">
            <i class="fa-solid fa-wallet text-lg"></i>
          </span>
          <p class="mt-3 text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400 md:mt-4 md:text-xs md:tracking-[0.18em]">Total Earnings</p>
          <p class="mt-1 text-xl font-black tracking-tight text-emerald-700 md:mt-2 md:text-2xl">{{ $money($displayTotalSalary) }}</p>
          <p class="mt-1 text-sm text-slate-500">Combined salary and income</p>
        </div>

        <div class="min-w-0 rounded-2xl border border-white/80 bg-white/90 p-3 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur md:rounded-[1.75rem] md:p-5">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-600 md:h-12 md:w-12 md:rounded-2xl">
            <i class="fa-solid fa-receipt text-lg"></i>
          </span>
          <p class="mt-3 text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400 md:mt-4 md:text-xs md:tracking-[0.18em]">Total Deductions</p>
          <p class="mt-1 text-xl font-black tracking-tight text-rose-700 md:mt-2 md:text-2xl">{{ $money($displayTotalDeduction) }}</p>
          <p class="mt-1 text-sm text-slate-500">Loans, taxes, and premiums</p>
        </div>

        <div class="min-w-0 rounded-2xl border border-white/80 bg-white/90 p-3 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur md:rounded-[1.75rem] md:p-5">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 md:h-12 md:w-12 md:rounded-2xl">
            <i class="fa-solid fa-money-bill-wave text-lg"></i>
          </span>
          <p class="mt-3 text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400 md:mt-4 md:text-xs md:tracking-[0.18em]">Net Pay</p>
          <p class="mt-1 text-xl font-black tracking-tight text-indigo-700 md:mt-2 md:text-2xl">{{ $money($selectedRecord?->net_pay) }}</p>
          <p class="mt-1 text-sm text-slate-500">Final payroll amount</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.35fr)]">
        <section id="payslip-review-queue" class="overflow-hidden rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur md:rounded-[1.75rem] md:p-6 md:shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
          <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                Employee Queue
              </div>
              <h2 class="mt-3 text-xl font-black tracking-tight text-slate-900 md:mt-4 md:text-2xl">Scanned employees</h2>
              <p class="mt-1 text-sm leading-5 text-slate-500 md:mt-2 md:leading-6">Choose an employee to view the payslip.</p>
            </div>
            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">{{ $scannedCount }} record(s)</span>
          </div>

          <div class="mt-4 md:mt-5">
            <label class="group flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 transition focus-within:border-emerald-300 focus-within:bg-white focus-within:shadow-sm md:gap-3 md:rounded-[1.25rem] md:px-4 md:py-3">
              <i class="fa-solid fa-magnifying-glass text-slate-400 transition group-focus-within:text-emerald-600"></i>
              <input
                id="employee_queue_search"
                type="text"
                placeholder="Search employees..."
                class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400"
              />
            </label>
          </div>

          <div class="payslip-employee-list mt-4 grid grid-cols-1 gap-2.5 md:mt-6 md:gap-4">
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
                class="employee-card rounded-xl border p-3 text-left transition md:rounded-[1.5rem] md:p-5 {{ $isSelected ? 'border-emerald-300 bg-emerald-50/80' : 'bg-white border-slate-200 hover:border-emerald-200' }}"
                data-employee-name="{{ $searchName }}"
                data-employee-id="{{ $searchId }}"
                data-pay-date="{{ strtolower(trim((string) $recordPayDate)) }}"
                data-scanned-at="{{ $searchScannedAt }}"
              >
                <div class="flex items-start justify-between gap-3 md:gap-4">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <p class="break-words text-sm font-semibold leading-5 text-slate-800 md:text-base">{{ $record->employee_name ?: 'Employee' }}</p>
                      <span class="hidden rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] sm:inline-flex {{ $isSelected ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $isSelected ? 'Selected' : 'Preview' }}
                      </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 md:text-sm">ID: {{ $record->employee_id ?: '-' }}</p>
                    <p class="text-xs text-slate-500 md:text-sm">Pay Date: {{ $recordPayDate }}</p>
                    <p class="mt-1 text-[11px] text-slate-400 md:mt-2 md:text-xs">{{ optional($record->scanned_at)->format('M d, Y h:i A') ?: 'Scanned' }}</p>
                  </div>
                  <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm md:h-11 md:w-11 md:rounded-2xl {{ $isSelected ? 'bg-emerald-100 text-emerald-600' : 'bg-sky-50 text-sky-600' }}">
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

        <section id="payslip-review-preview" class="space-y-6">
          @if ($selectedRecord)
          <div id="payslip-selected-meta" class="grid grid-cols-2 gap-2 md:grid-cols-3 md:gap-4">
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

          <div id="payslip-document-panel" class="overflow-hidden rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur">
            <div id="payslip-document-heading" class="mb-5 flex items-center justify-between gap-4">
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
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->basic_salary) ? 'payslip-empty-line' : '' }}"><span>Basic Salary</span><span>{{ $money($selectedRecord->basic_salary) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->living_allowance) ? 'payslip-empty-line' : '' }}"><span>Living Allowance</span><span>{{ $money($selectedRecord->living_allowance) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->extra_load) ? 'payslip-empty-line' : '' }}"><span>Extra Load</span><span>{{ $money($selectedRecord->extra_load) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->other_income) ? 'payslip-empty-line' : '' }}"><span>Other Income</span><span>{{ $money($selectedRecord->other_income) }}</span></div>
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
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->absences_amount) ? 'payslip-empty-line' : '' }}"><span>Absences Amount</span><span>{{ $money($selectedRecord->absences_amount) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->withholding_tax) ? 'payslip-empty-line' : '' }}"><span>Withholding Tax</span><span>{{ $money($selectedRecord->withholding_tax) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->salary_vale) ? 'payslip-empty-line' : '' }}"><span>Salary Vale</span><span>{{ $money($selectedRecord->salary_vale) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->pag_ibig_loan) ? 'payslip-empty-line' : '' }}"><span>Pag-ibig Loan</span><span>{{ $money($selectedRecord->pag_ibig_loan) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->pag_ibig_premium) ? 'payslip-empty-line' : '' }}"><span>Pag-ibig Premium</span><span>{{ $money($selectedRecord->pag_ibig_premium) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->sss_loan) ? 'payslip-empty-line' : '' }}"><span>SSS Loan</span><span>{{ $money($selectedRecord->sss_loan) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->sss_premium) ? 'payslip-empty-line' : '' }}"><span>SSS Premium</span><span>{{ $money($selectedRecord->sss_premium) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->peraa_loan) ? 'payslip-empty-line' : '' }}"><span>PERAA Loan</span><span>{{ $money($selectedRecord->peraa_loan) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->peraa_premium) ? 'payslip-empty-line' : '' }}"><span>PERAA Premium</span><span>{{ $money($selectedRecord->peraa_premium) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->philhealth_premium) ? 'payslip-empty-line' : '' }}"><span>Philhealth Premium</span><span>{{ $money($selectedRecord->philhealth_premium) }}</span></div>
                      <div class="flex justify-between {{ $isBlankAmount($selectedRecord->other_deduction) ? 'payslip-empty-line' : '' }}"><span>Other Deduction</span><span>{{ $money($selectedRecord->other_deduction) }}</span></div>
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

                <div id="payslip-net-pay" class="mt-6 lg:ml-auto lg:w-[360px] rounded-[1.5rem] border border-indigo-100 bg-[linear-gradient(135deg,rgba(99,102,241,0.10),rgba(14,165,233,0.10),rgba(255,255,255,0.96))] px-5 py-4">
                  <div class="flex items-center justify-between gap-4">
                    <div>
                      <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-indigo-700">Net Pay</p>
                      <p class="mt-1 text-sm text-slate-500">Final payroll amount after deductions</p>
                    </div>
                    <span class="text-2xl font-black tracking-tight text-indigo-700">{{ $money($selectedRecord->net_pay) }}</span>
                  </div>
                </div>
              </div>

              <div id="payslip-signatures" class="px-8 py-5 section-line text-sm text-slate-800">
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

              <div id="payslip-acknowledgement" class="px-8 py-6 text-center text-sm leading-6 text-slate-700">
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

  const payslipRegionIds = [
    'payslip-review-hero',
    'payslip-review-summary',
    'payslip-review-queue',
    'payslip-review-preview',
  ];
  let payslipPreviewRequest = null;

  const filterPayslipCards = () => {
    const headerSearchInput = document.querySelector('header input[placeholder="Search employees..."]');
    const queueSearchInput = document.getElementById('employee_queue_search');
    const employeeCards = Array.from(document.querySelectorAll('.employee-card'));
    const emptySearchMessage = document.getElementById('employee_search_empty');
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

  const payslipUrlWithTabSession = (href) => {
    const url = new URL(href, window.location.origin);
    const tabSession = new URL(window.location.href).searchParams.get('tab_session');
    if (tabSession) {
      url.searchParams.set('tab_session', tabSession);
    }
    return url;
  };

  const setPayslipRegionsLoading = (loading) => {
    payslipRegionIds.forEach((id) => {
      const region = document.getElementById(id);
      if (!region) return;
      region.style.transition = 'opacity 150ms ease';
      region.style.opacity = loading ? '0.55' : '1';
      region.style.pointerEvents = loading ? 'none' : '';
      region.setAttribute('aria-busy', String(loading));
    });
  };

  const loadPayslipPreview = async (href, pushHistory = true) => {
    const url = payslipUrlWithTabSession(href);
    payslipPreviewRequest?.abort();
    const requestController = new AbortController();
    payslipPreviewRequest = requestController;
    setPayslipRegionsLoading(true);

    try {
      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        cache: 'no-store',
        signal: requestController.signal,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-Payslip-Preview': '1',
        },
      });
      if (!response.ok) {
        throw new Error(`Unable to load payslip preview (${response.status}).`);
      }

      const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
      const replacements = payslipRegionIds.map((id) => {
        const current = document.getElementById(id);
        const incoming = nextDocument.getElementById(id);
        return { current, incoming };
      });
      if (replacements.some(({ current, incoming }) => !current || !incoming)) {
        throw new Error('The payslip preview response was incomplete.');
      }

      replacements.forEach(({ current, incoming }) => {
        current.replaceWith(incoming);
      });

      if (pushHistory) {
        window.history.pushState({ payslipPreview: true }, '', url.toString());
      }
      filterPayslipCards();
      window.refreshAdminSidebarSummary?.();
    } catch (error) {
      if (error.name !== 'AbortError') {
        window.location.assign(url.toString());
      }
    } finally {
      if (payslipPreviewRequest === requestController) {
        payslipPreviewRequest = null;
        setPayslipRegionsLoading(false);
      }
    }
  };

  document.addEventListener('click', (event) => {
    const card = event.target.closest('.employee-card');
    if (!card || event.defaultPrevented || event.button !== 0
      || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    event.preventDefault();
    loadPayslipPreview(card.href);
  });

  document.addEventListener('input', (event) => {
    if (event.target.matches('#employee_queue_search, header input[placeholder="Search employees..."]')) {
      filterPayslipCards();
    }
  });

  window.addEventListener('popstate', () => {
    loadPayslipPreview(window.location.href, false);
  });
</script>
</body>
</html>
