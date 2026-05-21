@include('components.adminHeader.scrollBehavior')

<header data-admin-scroll-header class="relative z-40 px-4 py-4 md:px-8 md:py-5">
    <div data-admin-scroll-card class="relative overflow-hidden rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] px-5 py-5 shadow-[0_24px_60px_rgba(3,19,29,0.34)] backdrop-blur-xl md:px-7 md:py-6">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
        <div class="relative">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-50">
            <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
            Hiring Flow
        </div>
        <h2 class="mt-4 text-3xl font-bold text-white">Interview Schedule</h2>
        <p class="mt-1 text-emerald-50/85">
            Upcoming and completed interviews
        </p>
        <p class="mt-3 inline-flex rounded-full border border-white/10 bg-white/8 px-3 py-1.5 text-xs font-medium text-emerald-50/80">{{ now()->format('l, F j, Y') }}</p>
        </div>
    </div>
</header>

