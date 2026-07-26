<button
    type="button"
    role="switch"
    aria-checked="false"
    aria-label="Switch between light and dark mode"
    data-theme-toggle
    class="group flex min-w-[14rem] items-center gap-2.5 rounded-2xl border border-white/15 bg-slate-900/75 px-3 py-2.5 text-left text-white shadow-sm backdrop-blur transition hover:border-white/25 hover:bg-slate-800"
>
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-200">
        <i class="fa-solid fa-circle-half-stroke"></i>
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-sm font-bold leading-4">Appearance</span>
        <span class="mt-1 block text-xs leading-4 text-slate-300" data-theme-label>Light mode</span>
    </span>
    <span class="relative h-7 w-12 shrink-0 rounded-full bg-slate-500 p-1 transition-colors duration-200 group-aria-checked:bg-blue-600">
        <span class="block h-5 w-5 rounded-full bg-slate-950 shadow-md ring-1 ring-white/10 transition-transform duration-200 group-aria-checked:translate-x-5"></span>
    </span>
</button>
