<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Hierarchy | Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
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

        .hierarchy-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(232, 248, 229, 0.96), rgba(241, 250, 239, 0.94) 50%, rgba(226, 245, 224, 0.96) 100%);
        }

        .tree-shell {
            max-width: 86rem;
            margin: 0 auto;
        }

        .tree-head-card {
            width: 20rem;
            min-height: 11rem;
        }

        .tree-card {
            width: 15rem;
            min-height: 11rem;
        }

        .tree-line-v {
            width: 2px;
            background: #7ccf83;
        }

        .tree-line-h {
            height: 2px;
            background: #7ccf83;
        }

        .tree-manager-grid {
            display: grid;
            justify-content: center;
            gap: 2rem 3rem;
        }

        .tree-staff-grid {
            display: grid;
            justify-content: center;
            gap: 1.25rem;
        }

        .tree-level-label {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            padding: 0.38rem 0.75rem;
            color: #047857;
            font-size: 0.64rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.08);
        }

        .tree-role-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 0.25rem 0.6rem;
            font-size: 0.6rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .tree-avatar {
            overflow: hidden;
        }

        .tree-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tree-employee-card {
            position: relative;
        }

        .tree-staff-item .tree-card {
            width: 13rem;
        }

        .tree-employee-button {
            width: 100%;
            text-align: inherit;
            cursor: pointer;
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .tree-employee-button:hover,
        .tree-employee-button:focus-visible,
        .tree-employee-card.is-open .tree-employee-button {
            transform: translateY(-4px);
            box-shadow: 0 20px 42px rgba(16, 185, 129, 0.18);
            border-color: #10b981;
            outline: none;
        }

        .tree-card-popover {
            position: absolute;
            left: 50%;
            top: calc(100% + 0.85rem);
            z-index: 20;
            width: 15rem;
            padding: 0.95rem 1rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.97);
            box-shadow: 0 22px 45px rgba(15, 23, 42, 0.14);
            backdrop-filter: blur(10px);
            opacity: 0;
            pointer-events: none;
            transform: translateX(-50%) translateY(10px);
            transition: opacity 180ms ease, transform 180ms ease;
        }

        .tree-card-popover::before {
            content: "";
            position: absolute;
            left: 50%;
            top: -0.4rem;
            width: 0.8rem;
            height: 0.8rem;
            background: rgba(255, 255, 255, 0.97);
            border-left: 1px solid rgba(16, 185, 129, 0.2);
            border-top: 1px solid rgba(16, 185, 129, 0.2);
            transform: translateX(-50%) rotate(45deg);
        }

        .tree-employee-card:hover .tree-card-popover,
        .tree-employee-card:focus-within .tree-card-popover,
        .tree-employee-card.is-open .tree-card-popover {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0);
        }

        .tree-card-popover__label {
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #059669;
        }

        .tree-card-popover__value {
            margin-top: 0.2rem;
            font-size: 0.8rem;
            line-height: 1.35;
            color: #0f172a;
            word-break: break-word;
        }

        @media (min-width: 1024px) {
            .tree-manager-grid.cols-1 {
                grid-template-columns: repeat(1, 15rem);
            }

            .tree-manager-grid.cols-2 {
                grid-template-columns: repeat(2, 15rem);
            }

            .tree-manager-grid.cols-3 {
                grid-template-columns: repeat(3, 15rem);
            }

            .tree-staff-grid.cols-1 {
                grid-template-columns: repeat(1, 13rem);
            }

            .tree-staff-grid.cols-2 {
                grid-template-columns: repeat(2, 13rem);
            }
        }

        @media (max-width: 1023px) {
            .tree-line-h,
            .tree-line-v.desktop-line,
            .tree-branch {
                display: none !important;
            }

            .tree-manager-grid,
            .tree-staff-grid {
                grid-template-columns: 1fr;
                justify-items: stretch;
                width: 100%;
            }

            .tree-head-card,
            .tree-card {
                width: 100%;
                min-height: 0;
            }

            .tree-employee-card,
            .tree-staff-item .tree-card {
                width: 100%;
            }

            .tree-card-popover {
                display: none;
                position: static;
                width: 100%;
                margin-top: 0.85rem;
                transform: none;
            }

            .tree-employee-card.is-open .tree-card-popover {
                display: block;
                opacity: 1;
                pointer-events: auto;
            }

            .tree-card-popover::before {
                display: none;
            }

            .tree-manager-branch {
                position: relative;
                padding-left: 1.15rem;
                border-left: 2px solid #6ee7b7;
            }

            .tree-manager-branch::before,
            .tree-staff-item::before {
                content: "";
                position: absolute;
                left: -1.15rem;
                top: 2rem;
                width: 1.15rem;
                height: 2px;
                background: #6ee7b7;
            }

            .tree-staff-grid {
                position: relative;
                margin-top: 1rem !important;
                margin-left: 1rem;
                width: calc(100% - 1rem);
                gap: 0.75rem;
                border-left: 2px solid #a7f3d0;
                padding-left: 1rem;
            }

            .tree-staff-item {
                position: relative;
            }

            .tree-staff-item::before {
                left: -1rem;
                width: 1rem;
                background: #a7f3d0;
            }

            .tree-employee-button {
                padding: 1rem !important;
            }

            .tree-employee-button:hover,
            .tree-employee-button:focus-visible,
            .tree-employee-card.is-open .tree-employee-button {
                transform: none;
            }
        }

        @media (max-width: 640px) {
            main { margin-left: 0 !important; }
            .tree-page-content { padding: 5.25rem 0.9rem 2rem !important; }
            .tree-shell-header { text-align: left !important; padding: 0 0.25rem; }
            .tree-shell-header h1 { font-size: 1.65rem !important; line-height: 1.15 !important; }
            .tree-shell-header p { font-size: 0.82rem !important; line-height: 1.5; }
            .tree-root { margin-top: 2rem !important; }
            .tree-level-label { padding: .32rem .65rem; font-size: .58rem; letter-spacing: .1em; }
            .tree-head-card {
                display: grid;
                grid-template-columns: 3.25rem minmax(0, 1fr);
                align-items: center;
                gap: .25rem .85rem;
                padding: 1rem !important;
                border-radius: 1.15rem !important;
                text-align: left !important;
                box-shadow: 0 14px 34px rgba(6,95,70,.18) !important;
            }
            .tree-head-card > :first-child {
                grid-column: 1;
                grid-row: 1 / 5;
                width: 3.25rem !important;
                height: 3.25rem !important;
                margin: 0 !important;
                font-size: .9rem !important;
            }
            .tree-head-card h2,
            .tree-head-card p,
            .tree-head-card .tree-role-badge {
                grid-column: 2;
                margin-top: 0 !important;
            }
            .tree-head-card h2 { font-size: .92rem !important; }
            .tree-head-card p { font-size: .68rem !important; }
            .tree-head-card .tree-role-badge { justify-self: start; font-size: .52rem; }
            .tree-root > .tree-line-v { height: 1.4rem !important; }
            .tree-manager-grid {
                position: relative;
                margin-top: .8rem !important;
                margin-left: 0;
                width: 100%;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: .75rem;
                border-left: 0;
                padding: 1.45rem 0 0;
            }
            .tree-manager-grid::before {
                content: "";
                position: absolute;
                left: 25%;
                right: 25%;
                top: 0;
                height: 2px;
                background: #6ee7b7;
            }
            .tree-manager-grid.single-node::before {
                left: 50%;
                right: 50%;
            }
            .tree-manager-branch {
                padding-left: 0;
                border-left: 0;
            }
            .tree-manager-branch::before {
                left: 50%;
                top: -1.45rem;
                width: 2px;
                height: 1.45rem;
            }
            .tree-employee-button {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: .2rem;
                min-height: 9.2rem;
                padding: .8rem .55rem !important;
                border-width: 1px !important;
                border-radius: .95rem !important;
                text-align: center !important;
                box-shadow: 0 8px 22px rgba(15,118,110,.08) !important;
            }
            .tree-employee-button > :first-child {
                width: 2.5rem !important;
                height: 2.5rem !important;
                margin: 0 !important;
                font-size: .78rem !important;
            }
            .tree-employee-button h3,
            .tree-employee-button h4 {
                margin-top: .25rem !important;
                font-size: .72rem !important;
                line-height: 1.25 !important;
            }
            .tree-employee-button .tree-role-badge {
                margin-top: 0 !important;
                padding: .22rem .42rem;
                font-size: .44rem;
                white-space: nowrap;
            }
            .tree-employee-button p {
                margin-top: 0 !important;
                font-size: .58rem !important;
                line-height: 1.25;
            }
            .tree-staff-grid {
                margin-left: .65rem;
                width: calc(100% - .65rem);
                gap: .6rem;
                padding-left: .8rem;
            }
            .tree-staff-item::before { left: -.8rem; width: .8rem; top: 1.65rem; }
            .tree-staff-item .tree-employee-button { background: rgba(255,255,255,.8); }
            .tree-card-popover {
                margin-top: .45rem;
                padding: .75rem;
                border-radius: .8rem;
                box-shadow: 0 10px 24px rgba(15,23,42,.08);
            }
            .tree-card-popover > div { display: grid; grid-template-columns: 5.5rem minmax(0,1fr); gap: .5rem; }
            .tree-card-popover > div + div { margin-top: .5rem !important; }
            .tree-card-popover__label,
            .tree-card-popover__value { margin-top: 0; font-size: .62rem; }
        }
    </style>
</head>
<body class="hierarchy-page text-slate-900">
<div class="flex min-h-screen">
    @include('components.employeeSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        <div class="tree-page-content px-4 pb-10 pt-20 md:px-8">
            @php
                $managerCountValue = max($managerNodes->count(), 1);
                $managerGridClass = 'cols-'.min($managerCountValue, 3);
            @endphp

            <section class="tree-shell">
                <div class="tree-shell-header text-center">
                    <h1 class="text-3xl font-black tracking-tight text-emerald-900 md:text-5xl">
                        {{ $departmentName }} Employee Hierarchy
                    </h1>
                    <p class="mt-2 text-base text-emerald-800 md:text-[1.05rem]">
                        View the Head of Department and employees under each level
                    </p>
                </div>

                @if ($headNode)
                    <div class="tree-root mt-16 flex flex-col items-center">
                        <div class="tree-level-label mb-3"><i class="fa fa-star"></i><span class="hidden sm:inline">Level 1 - Department Head</span><span class="sm:hidden">Level 1 - Head</span></div>
                        <article class="tree-head-card rounded-[1.1rem] bg-gradient-to-br from-emerald-900 via-emerald-800 to-green-600 px-4 py-5 text-center text-white shadow-[0_22px_60px_rgba(34,139,34,0.22)]">
                            @if (!empty($headNode['photo_url']))
                                <div class="tree-avatar mx-auto h-[3.85rem] w-[3.85rem] rounded-full ring-8 ring-white/8">
                                    <img src="{{ $headNode['photo_url'] }}" alt="{{ $headNode['name'] }}">
                                </div>
                            @else
                                <div class="mx-auto flex h-[3.85rem] w-[3.85rem] items-center justify-center rounded-full bg-white/18 text-[1.1rem] font-black ring-8 ring-white/8">
                                    {{ $headNode['initials'] }}
                                </div>
                            @endif
                            <h2 class="mt-4 text-[0.95rem] font-black leading-snug">{{ $headNode['name'] }}</h2>
                            <span class="tree-role-badge mt-2 bg-white/15 text-emerald-50">Department Leader</span>
                            <p class="mt-1 text-[0.72rem] text-emerald-50">{{ $headNode['title'] }}</p>
                            <p class="mt-1.5 text-[0.72rem] font-semibold text-white">{{ $headNode['team'] }}</p>
                        </article>

                        @if ($managerNodes->isNotEmpty())
                            <div class="tree-line-v mt-0 h-7"></div>
                        @endif
                    </div>
                @endif

                @if ($managerNodes->isNotEmpty())
                    <div class="mt-8 text-center lg:mt-0">
                        <div class="tree-level-label"><i class="fa fa-sitemap"></i><span class="hidden sm:inline">Level 2 - {{ $hasActualManagers ? 'Managers & Team Leads' : 'Direct Reports' }}</span><span class="sm:hidden">Level 2 - {{ $hasActualManagers ? 'Team Leads' : 'Direct Reports' }}</span></div>
                    </div>
                    <div class="mx-auto hidden w-fit flex-col items-center lg:flex">
                        <div class="tree-line-v h-5"></div>
                        <div class="tree-line-h" style="width: calc({{ max($managerNodes->count() - 1, 0) }} * (15rem + 3rem));"></div>
                        <div class="tree-manager-grid {{ $managerGridClass }}" style="margin-top: 0;">
                            @foreach ($managerNodes as $managerNode)
                                <div class="flex flex-col items-center">
                                    <div class="tree-line-v desktop-line h-5"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tree-manager-grid {{ $managerGridClass }} {{ $managerNodes->count() === 1 ? 'single-node' : '' }} mt-0">
                        @foreach ($managerNodes as $managerNode)
                            @php
                                $staffCountValue = max($managerNode['employees']->count(), 1);
                                $staffGridClass = 'cols-'.min($staffCountValue, 2);
                            @endphp

                            <div class="tree-manager-branch flex flex-col items-center">
                                <div class="tree-employee-card" data-tree-employee-card>
                                    <article
                                        tabindex="0"
                                        role="button"
                                        aria-label="View {{ $managerNode['name'] }} information"
                                        class="tree-employee-button tree-card rounded-[1.1rem] border-2 border-emerald-400 bg-white px-4 py-5 text-center shadow-[0_18px_40px_rgba(110,231,183,0.14)]"
                                    >
                                        @if (!empty($managerNode['photo_url']))
                                            <div class="tree-avatar mx-auto h-[3.25rem] w-[3.25rem] rounded-full border border-emerald-200">
                                                <img src="{{ $managerNode['photo_url'] }}" alt="{{ $managerNode['name'] }}">
                                            </div>
                                        @else
                                            <div class="mx-auto flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full bg-emerald-100 text-[1rem] font-black text-emerald-900">
                                                {{ $managerNode['initials'] }}
                                            </div>
                                        @endif
                                        <h3 class="mt-4 text-[0.92rem] font-black leading-snug text-slate-900">{{ $managerNode['name'] }}</h3>
                                        <span class="tree-role-badge mt-2 {{ $managerNode['is_manager'] ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $managerNode['is_manager'] ? 'Team Lead' : 'Staff' }}</span>
                                        <p class="mt-1 text-[0.72rem] text-slate-700">{{ $managerNode['title'] }}</p>
                                        <p class="mt-1.5 text-[0.72rem] font-semibold text-emerald-700">{{ $managerNode['team'] }}</p>
                                    </article>

                                    <div class="tree-card-popover">
                                        <div>
                                            <p class="tree-card-popover__label">Employee ID</p>
                                            <p class="tree-card-popover__value">{{ $managerNode['employee_id'] }}</p>
                                        </div>
                                        <div class="mt-3">
                                            <p class="tree-card-popover__label">Email</p>
                                            <p class="tree-card-popover__value">{{ $managerNode['email'] }}</p>
                                        </div>
                                        <div class="mt-3">
                                            <p class="tree-card-popover__label">Employment Status</p>
                                            <p class="tree-card-popover__value">{{ $managerNode['status'] }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if ($managerNode['employees']->isNotEmpty())
                                    <div class="tree-line-v desktop-line h-7"></div>
                                    <div class="tree-level-label ml-4 mt-3 self-start lg:hidden"><i class="fa fa-users"></i> Level 3 - Team Members</div>
                                    <div class="hidden lg:flex lg:flex-col lg:items-center">
                                        <div class="tree-line-h" style="width: calc({{ max($managerNode['employees']->count() - 1, 0) }} * (13rem + 1.25rem));"></div>
                                        <div class="tree-staff-grid {{ $staffGridClass }} mt-0">
                                            @foreach ($managerNode['employees'] as $employeeNode)
                                                <div class="flex flex-col items-center">
                                                    <div class="tree-line-v desktop-line h-5"></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="tree-staff-grid {{ $staffGridClass }} mt-0">
                                        @foreach ($managerNode['employees'] as $employeeNode)
                                            <div class="tree-employee-card tree-staff-item" data-tree-employee-card>
                                                <article
                                                    tabindex="0"
                                                    role="button"
                                                    aria-label="View {{ $employeeNode['name'] }} information"
                                                    class="tree-employee-button tree-card rounded-[1.1rem] border-2 border-emerald-400 bg-white px-4 py-5 text-center shadow-[0_16px_36px_rgba(110,231,183,0.14)]"
                                                >
                                                    @if (!empty($employeeNode['photo_url']))
                                                        <div class="tree-avatar mx-auto h-[3.25rem] w-[3.25rem] rounded-full border border-emerald-200">
                                                            <img src="{{ $employeeNode['photo_url'] }}" alt="{{ $employeeNode['name'] }}">
                                                        </div>
                                                    @else
                                                        <div class="mx-auto flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-full bg-emerald-100 text-[1rem] font-black text-emerald-900">
                                                            {{ $employeeNode['initials'] }}
                                                        </div>
                                                    @endif
                                                    <h4 class="mt-4 text-[0.92rem] font-black leading-snug text-slate-900">{{ $employeeNode['name'] }}</h4>
                                                    <span class="tree-role-badge mt-2 bg-emerald-50 text-emerald-700">Team Member</span>
                                                    <p class="mt-1 text-[0.72rem] text-slate-700">{{ $employeeNode['title'] }}</p>
                                                    <p class="mt-1.5 text-[0.72rem] font-semibold text-emerald-700">{{ $employeeNode['team'] }}</p>
                                                </article>

                                                <div class="tree-card-popover">
                                                    <div>
                                                        <p class="tree-card-popover__label">Employee ID</p>
                                                        <p class="tree-card-popover__value">{{ $employeeNode['employee_id'] }}</p>
                                                    </div>
                                                    <div class="mt-3">
                                                        <p class="tree-card-popover__label">Email</p>
                                                        <p class="tree-card-popover__value">{{ $employeeNode['email'] }}</p>
                                                    </div>
                                                    <div class="mt-3">
                                                        <p class="tree-card-popover__label">Employment Status</p>
                                                        <p class="tree-card-popover__value">{{ $employeeNode['status'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($headNode)
                    <div class="mt-10 text-center text-sm text-emerald-800">
                        No second-level employees found for this department.
                    </div>
                @else
                    <div class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 text-center text-amber-800">
                        No hierarchy records were grouped for this department yet.
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>
<script>
    (function () {
        const employeeCards = Array.from(document.querySelectorAll('[data-tree-employee-card]'));
        if (!employeeCards.length) {
            return;
        }

        const closeAllCards = () => {
            employeeCards.forEach((card) => card.classList.remove('is-open'));
        };

        employeeCards.forEach((card) => {
            const trigger = card.querySelector('.tree-employee-button');
            if (!trigger) {
                return;
            }

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = !card.classList.contains('is-open');
                closeAllCards();
                card.classList.toggle('is-open', willOpen);
            });

            trigger.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                const willOpen = !card.classList.contains('is-open');
                closeAllCards();
                card.classList.toggle('is-open', willOpen);
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-tree-employee-card]')) {
                closeAllCards();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllCards();
            }
        });
    })();
</script>
</body>
</html>
