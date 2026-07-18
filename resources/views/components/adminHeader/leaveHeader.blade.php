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

<header data-admin-scroll-header class="relative z-40 px-4 py-4 md:px-8 md:py-5">
    <div data-admin-scroll-card class="relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] shadow-[0_24px_60px_rgba(3,19,29,0.34)] backdrop-blur-xl">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="absolute -left-8 top-6 h-28 w-28 rounded-full bg-cyan-300/10 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-36 w-36 translate-x-10 -translate-y-10 rounded-full bg-emerald-300/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 px-5 py-5 md:px-7 md:py-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-50">
                    <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                    {{ $headerBadge }}
                </div>

                <h2 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">{{ $headerTitle }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85 md:text-base">{{ $headerSubtitle }}</p>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs font-medium text-emerald-50/80">
                    <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ now()->format('l, F j, Y') }}</span>
                    <span data-admin-leave-live-text="header-pending" class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $pendingRequestCount }} pending request(s)</span>
                    <span data-admin-leave-live-text="header-approved" class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ $approvedRequestCount }} approved this month</span>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.adminLeaveManagement') }}" class="rounded-[1.75rem] border border-white/10 bg-white/10 p-4 shadow-[0_16px_34px_rgba(3,19,29,0.2)] backdrop-blur xl:min-w-[360px]">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-50/70">Filter Month</p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="flex flex-1 items-center gap-3 rounded-2xl border border-white/10 bg-white px-4 py-3 transition focus-within:border-emerald-300">
                        <i class="fa-regular fa-calendar text-slate-400"></i>
                        <input
                            type="month"
                            name="month"
                            value="{{ $selectedMonthValue }}"
                            class="w-full bg-transparent text-sm font-medium text-slate-700 outline-none"
                        />
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-300 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-200">
                        <i class="fa-solid fa-sliders"></i>
                        Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>
