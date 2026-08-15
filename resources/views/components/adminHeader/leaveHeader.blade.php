@php
    $selectedMonthValue = $selectedMonth ?? now()->format('Y-m');
    $selectedMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonthValue)->format('F Y');
    $pendingRequestCount = (int) ($pendingRequestCount ?? ($pendingLeaveRequests ?? collect())->count());
    $approvedRequestCount = ($monthRecords ?? collect())->count();
    $headerTitle = $headerTitle ?? "Leave Overview for {$selectedMonthLabel}";
    $headerSubtitle = $headerSubtitle ?? "Review pending requests, monitor approved leave usage, and keep this month's team availability visible at a glance.";
    $headerBadge = $headerBadge ?? 'Leave Operations';
@endphp

@include('components.adminHeader.scrollBehavior')

<style>
    @media (max-width: 767px) {
        .leave-management-header {
            padding: 0.625rem 0.75rem 0.25rem;
        }

        .leave-management-header-card {
            border-radius: 1.25rem;
        }

        .leave-management-header-layout {
            gap: 0.75rem;
            padding: 0.9rem;
            padding-left: 4rem;
        }

        .leave-management-header-badge {
            display: none;
        }

        .leave-management-header-title {
            margin-top: 0;
            font-size: 1.35rem;
            line-height: 1.2;
        }

        .leave-management-header-subtitle {
            margin-top: 0.35rem;
            font-size: 0.75rem;
            line-height: 1.4;
            text-align: center;
        }

        .leave-management-header-stats {
            margin-top: 0.55rem;
            justify-content: center;
            gap: 0.35rem;
            font-size: 0.65rem;
        }

        .leave-management-header-stats > span {
            padding: 0.25rem 0.45rem;
        }

        .leave-management-month-filter {
            border-radius: 0.85rem;
            padding: 0.6rem;
        }

        .leave-management-month-filter > p {
            font-size: 0.6rem;
            text-align: center;
        }

        .leave-management-month-filter-row {
            margin-top: 0.45rem;
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
        }

        .leave-management-month-input {
            min-width: 0;
            padding: 0.55rem 0.65rem;
            border-radius: 0.7rem;
        }

        .leave-management-month-input input {
            font-size: 0.75rem;
            text-align: center;
        }

        .leave-management-month-apply {
            flex: 0 0 auto;
            padding: 0.55rem 0.75rem;
            font-size: 0.75rem;
        }
    }
</style>

<header data-admin-scroll-header class="leave-management-header relative z-40 px-4 py-4 md:px-8 md:py-5">
    <div data-admin-scroll-card class="leave-management-header-card relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] shadow-[0_24px_60px_rgba(3,19,29,0.34)] backdrop-blur-xl">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="absolute -left-8 top-6 h-28 w-28 rounded-full bg-cyan-300/10 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-36 w-36 translate-x-10 -translate-y-10 rounded-full bg-emerald-300/20 blur-3xl"></div>

        <div class="leave-management-header-layout relative flex flex-col gap-5 px-5 py-5 md:px-7 md:py-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl min-w-0">
                <div class="leave-management-header-badge inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-50">
                    <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                    {{ $headerBadge }}
                </div>

                <h2 class="leave-management-header-title mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">{{ $headerTitle }}</h2>
                <p class="leave-management-header-subtitle mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85 md:text-base">{{ $headerSubtitle }}</p>

                <div class="leave-management-header-stats mt-4 flex flex-wrap items-center gap-3 text-xs font-medium text-emerald-50/80">
                    <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ now()->format('l, F j, Y') }}</span>
                    <span data-admin-leave-live-text="header-pending" class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $pendingRequestCount }} pending request(s)</span>
                    <span data-admin-leave-live-text="header-approved" class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $approvedRequestCount }} approved this month</span>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.adminLeaveManagement') }}" class="leave-management-month-filter rounded-[1.75rem] border border-white/10 bg-white/10 p-4 shadow-[0_16px_34px_rgba(3,19,29,0.2)] backdrop-blur xl:min-w-[360px]">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-50/70">Filter Month</p>
                <div class="leave-management-month-filter-row mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="leave-management-month-input flex flex-1 items-center gap-3 rounded-2xl border border-white/10 bg-white px-4 py-3 transition focus-within:border-emerald-300">
                        <i class="fa-regular fa-calendar text-slate-400"></i>
                        <input
                            type="month"
                            name="month"
                            value="{{ $selectedMonthValue }}"
                            class="w-full bg-transparent text-sm font-medium text-slate-700 outline-none"
                        />
                    </label>
                    <button type="submit" class="leave-management-month-apply inline-flex items-center justify-center gap-2 rounded-full bg-emerald-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-200">
                        <i class="fa-solid fa-sliders"></i>
                        Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>
