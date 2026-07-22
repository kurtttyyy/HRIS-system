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
                        <a
                            href="{{ route('admin.employeeImport', array_filter(['tab_session' => request()->query('tab_session')])) }}"
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/30 bg-emerald-300/15 px-3 py-1.5 text-emerald-50 transition hover:border-emerald-300/60 hover:bg-emerald-300/25"
                            title="Upload an employee Excel file"
                        >
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            Insert
                        </a>
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
                                x-model="searchInput"
                                @input="beginEmployeeSearch()"
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
                            <div class="relative" @click.outside="exportMenuOpen = false">
                                <button
                                    type="button"
                                    @click="exportMenuOpen = !exportMenuOpen"
                                    :aria-expanded="exportMenuOpen"
                                    class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-200"
                                >
                                    <i class="fa-solid fa-file-excel text-xs"></i>
                                    Excel
                                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="exportMenuOpen ? 'rotate-180' : ''"></i>
                                </button>

                                <div
                                    x-cloak
                                    x-show="exportMenuOpen"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="translate-y-2 scale-95 opacity-0"
                                    x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                                    x-transition:leave-end="translate-y-2 scale-95 opacity-0"
                                    class="absolute right-0 z-[80] mt-3 w-72 origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 text-slate-800 shadow-[0_22px_55px_rgba(15,23,42,0.25)]"
                                >
                                    <p class="px-3 pb-2 pt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Choose export</p>
                                    <button type="button" @click="exportMenuOpen = false; window.exportAdminEmployeesExcel()" class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-emerald-50">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><i class="fa-solid fa-table-list"></i></span>
                                        <span><strong class="block text-sm">Whole employee table</strong><small class="mt-0.5 block text-xs text-slate-500">Download all visible employee fields.</small></span>
                                    </button>
                                    <button type="button" @click="exportMenuOpen = false; window.exportAdminEmployeePinsExcel()" class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-amber-50">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><i class="fa-solid fa-key"></i></span>
                                        <span><strong class="block text-sm">Temporary PIN list</strong><small class="mt-0.5 block text-xs text-slate-500">Name, Employee ID, and PIN only.</small></span>
                                    </button>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="searchInput = ''; department = 'All'; statusFilter = 'All'; applyEmployeeDirectoryFilters()"
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
