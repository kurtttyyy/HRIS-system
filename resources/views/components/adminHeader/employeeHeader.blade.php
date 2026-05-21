@include('components.adminHeader.scrollBehavior')

<header data-admin-scroll-header class="relative z-40 px-4 py-4 md:px-8 md:py-5">
    <div data-admin-scroll-card class="relative overflow-visible rounded-[2rem] border border-emerald-950/70 bg-[linear-gradient(135deg,_#020617_0%,_#020617_42%,_#111827_68%,_#064e3b_100%)] shadow-[0_24px_60px_rgba(3,19,29,0.34)] backdrop-blur-xl">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-[inherit]">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.14),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(110,231,183,0.14),_transparent_32%)]"></div>
            <div class="absolute -left-10 top-6 h-28 w-28 rounded-full bg-cyan-300/10 blur-3xl"></div>
            <div class="absolute right-0 top-0 h-36 w-36 translate-x-10 -translate-y-10 rounded-full bg-emerald-300/20 blur-3xl"></div>
        </div>

        <div class="relative flex flex-col gap-6 px-5 py-5 md:px-7 md:py-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-50">
                    <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                    Workforce Center
                </div>

                <div class="mt-4 min-w-0">
                    <h2 class="text-3xl font-black tracking-tight text-white md:text-4xl">Employee Directory</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85 md:text-base">
                        Search profiles, narrow by department, and monitor employee status from one polished workspace.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs font-medium text-emerald-50/80">
                        <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">{{ now()->format('l, F j, Y') }}</span>
                        <button
                            type="button"
                            @click="showDepartmentSummary = true; $nextTick(() => document.getElementById('department-staffing-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5 transition hover:border-emerald-300/40 hover:bg-white/15"
                        >
                            Total Record
                        </button>
                        <button
                            type="button"
                            @click="showDepartmentSummary = false; viewMode = viewMode === 'table' ? 'cards' : 'table'"
                            class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5 text-emerald-50 transition hover:border-emerald-300/40 hover:bg-white/15"
                            x-text="viewMode === 'table' ? 'View Cards' : 'View Table'"
                        ></button>
                    </div>
                    </div>
                </div>

            <div class="w-full xl:max-w-2xl">
                <div class="rounded-[1.75rem] border border-white/10 bg-white/10 p-4 shadow-[0_16px_34px_rgba(3,19,29,0.2)] backdrop-blur">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,0.8fr)]">
                        <label class="group relative flex items-center rounded-2xl border border-white/10 bg-white px-4 py-3 transition focus-within:border-emerald-300 focus-within:shadow-sm">
                            <i class="fa-solid fa-magnifying-glass text-slate-400 transition group-focus-within:text-emerald-600"></i>
                            <input
                                type="text"
                                x-model="search"
                                @input.debounce.500ms="applyEmployeeDirectoryFilters()"
                                placeholder="Search by employee name..."
                                class="w-full bg-transparent pl-3 pr-2 text-sm text-slate-700 outline-none placeholder:text-slate-400"
                            >
                        </label>

                        <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white px-4 py-3 transition focus-within:border-emerald-300 focus-within:shadow-sm">
                            <i class="fa-solid fa-layer-group text-slate-400"></i>
                            <select
                                x-model="department"
                                @change="applyEmployeeDirectoryFilters()"
                                class="w-full bg-transparent text-sm font-medium text-slate-700 outline-none"
                            >
                                <option value="All">All Departments</option>
                                @foreach (($departmentOptions ?? collect()) as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="mt-4 grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="statusFilter = 'All'; applyEmployeeDirectoryFilters()"
                                :class="statusFilter === 'All'
                                    ? 'border-white/15 bg-slate-950 text-white shadow-md'
                                    : 'border-white/10 bg-white/10 text-emerald-50 hover:border-white/20 hover:bg-white/15'"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                            >
                                All
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'Active'; applyEmployeeDirectoryFilters()"
                                :class="statusFilter === 'Active'
                                    ? 'border-emerald-300 bg-emerald-300 text-slate-950 shadow-md'
                                    : 'border-emerald-300/20 bg-emerald-300/10 text-emerald-50 hover:bg-emerald-300/20'"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                            >
                                Active
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'On Leave'; applyEmployeeDirectoryFilters()"
                                :class="statusFilter === 'On Leave'
                                    ? 'border-amber-300 bg-amber-300 text-slate-950 shadow-md'
                                    : 'border-amber-300/20 bg-amber-300/10 text-amber-100 hover:bg-amber-300/20'"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                            >
                                On Leave
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'Inactive'; applyEmployeeDirectoryFilters()"
                                :class="statusFilter === 'Inactive'
                                    ? 'border-rose-300 bg-rose-300 text-slate-950 shadow-md'
                                    : 'border-rose-300/20 bg-rose-300/10 text-rose-100 hover:bg-rose-300/20'"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                            >
                                Inactive
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'Missing Info'; applyEmployeeDirectoryFilters()"
                                :class="statusFilter === 'Missing Info'
                                    ? 'border-orange-200 bg-orange-200 text-slate-950 shadow-md'
                                    : 'border-orange-300/25 bg-orange-300/10 text-orange-100 hover:bg-orange-300/20'"
                                class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition"
                            >
                                Missing Info
                                <span
                                    class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-orange-100 px-1.5 py-0.5 text-[11px] font-bold leading-none text-orange-700"
                                    x-text="employeeIndex.filter(emp => emp.has_missing_info).length"
                                ></span>
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                            <button
                                type="button"
                                @click="window.exportAdminEmployeesExcel({ search, department, statusFilter })"
                                class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-200"
                            >
                                <i class="fa-solid fa-file-excel text-xs"></i>
                                Excel
                            </button>
                            <button
                                type="button"
                                @click="search = ''; department = 'All'; statusFilter = 'All'; applyEmployeeDirectoryFilters()"
                                class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-full border border-white/10 bg-white/8 px-4 py-2 text-sm font-semibold text-emerald-50 transition hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/15"
                            >
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                                Reset filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
