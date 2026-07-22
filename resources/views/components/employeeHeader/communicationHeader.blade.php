@php
    $directoryMembers = collect($admins ?? []);
    $memberCount = $directoryMembers->count();
    $availableCount = $directoryMembers->filter(function ($member) {
        $status = strtolower(trim((string) ($member->status ?? '')));
        return in_array($status, ['approved', 'available'], true);
    })->count();
    $roleCount = $directoryMembers
        ->map(fn ($member) => trim((string) ($member->job_role ?? $member->role ?? '')))
        ->filter()
        ->unique()
        ->count();
@endphp

<style>
    @keyframes communication-header-float {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    .employee-communication-header {
        transition: background-color 0.28s ease, box-shadow 0.28s ease;
    }

    .employee-communication-header__card,
    .employee-communication-header__title,
    .employee-communication-header__subtitle,
    .employee-communication-header__stats,
    .employee-communication-header__panel {
        transition: all 0.28s ease;
    }

    .employee-communication-header__orb {
        animation: communication-header-float 6s ease-in-out infinite;
        transition: opacity 0.28s ease, transform 0.28s ease;
    }

    .employee-communication-header.is-scrolled {
        background-color: rgba(248, 250, 252, 0.82);
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    }

    .employee-communication-header.is-scrolled .employee-communication-header__card {
        border-radius: 1.45rem;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.18);
        transform: scale(0.985);
    }

    .employee-communication-header.is-scrolled .employee-communication-header__title {
        transform: translateY(-2px);
    }

    .employee-communication-header.is-scrolled .employee-communication-header__subtitle,
    .employee-communication-header.is-scrolled .employee-communication-header__stats,
    .employee-communication-header.is-scrolled .employee-communication-header__panel {
        opacity: 0.88;
        transform: translateY(-4px);
    }

    .employee-communication-header.is-scrolled .employee-communication-header__orb {
        opacity: 0.55;
        transform: scale(0.92);
    }
</style>

<header id="employee-communication-header" class="employee-communication-header relative z-10 border-b border-slate-200/80 bg-white/80 px-4 py-4 backdrop-blur-xl md:px-8 md:py-5">
    <div class="employee-communication-header__card relative overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-emerald-950 to-teal-700 px-5 py-6 text-white shadow-[0_24px_80px_rgba(15,23,42,0.28)] md:px-8 md:py-8">
        <div class="employee-communication-header__orb absolute -left-8 top-4 h-24 w-24 rounded-full bg-white/10 blur-3xl"></div>
        <div class="employee-communication-header__orb absolute right-6 top-3 h-20 w-20 rounded-full bg-emerald-200/20 blur-3xl"></div>

        <div class="relative grid gap-6 lg:grid-cols-[1.6fr_0.9fr] lg:items-end">
            <div class="space-y-5">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-100">
                    Employee Communication
                </div>

                <div>
                    <h2 class="employee-communication-header__title text-3xl font-black tracking-tight text-white md:text-4xl">Team Directory</h2>
                    <p class="employee-communication-header__subtitle mt-2 max-w-2xl text-sm leading-6 text-emerald-50 md:text-base">
                        Reach the right colleague faster with a cleaner directory, clearer profile cards, and quick status visibility.
                    </p>
                </div>

                <div class="employee-communication-header__stats flex flex-wrap gap-3 text-sm">
                    <div class="min-w-[120px] rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-100">Members</p>
                        <p class="mt-1 text-xl font-bold text-white">{{ $memberCount }}</p>
                    </div>
                    <div class="min-w-[120px] rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-100">Available</p>
                        <p class="mt-1 text-xl font-bold text-white">{{ $availableCount }}</p>
                    </div>
                    <div class="min-w-[120px] rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-100">Roles</p>
                        <p class="mt-1 text-xl font-bold text-white">{{ $roleCount }}</p>
                    </div>
                </div>
            </div>

            <div class="employee-communication-header__panel rounded-[1.75rem] border border-white/15 bg-white/10 p-4 backdrop-blur-sm md:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Profile Access</p>
                        <p class="mt-2 text-sm leading-6 text-emerald-50">
                            Keep your own details current so teammates can contact you using accurate information.
                        </p>
                    </div>

                    <div class="relative group shrink-0">
                        <button class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white transition hover:bg-white/20">
                            <i class="fa fa-user text-lg"></i>
                        </button>

                        <div class="absolute right-0 z-50 mt-3 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl opacity-0 invisible transition-all duration-200 group-hover:visible group-hover:opacity-100">
                            <a href="{{ route('employee.employeeProfile', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50">
                                <i class="fa fa-user text-slate-500"></i>
                                My Profile
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                @if (request()->filled('tab_session'))
                                    <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
                                @endif
                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-rose-600 transition hover:bg-rose-50">
                                    <i class="fa fa-sign-out text-rose-500"></i>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <a href="{{ route('employee.employeeProfile', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5 hover:bg-emerald-50">
                    <i class="fa fa-pen"></i>
                    Edit Profile
                </a>
            </div>
        </div>
    </div>
</header>

<script>
    (function () {
        const header = document.getElementById('employee-communication-header');
        if (!header) {
            return;
        }

        let isScrolled = false;

        const updateHeaderOnScroll = () => {
            const nextScrolled = window.scrollY > 24;
            if (nextScrolled === isScrolled) {
                return;
            }

            isScrolled = nextScrolled;
            header.classList.toggle('is-scrolled', isScrolled);
        };

        updateHeaderOnScroll();
        window.addEventListener('scroll', updateHeaderOnScroll, { passive: true });
    })();
</script>
