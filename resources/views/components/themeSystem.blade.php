<script>
    (() => {
        const savedTheme = localStorage.getItem('hris-color-theme');
        const theme = savedTheme === 'dark' || savedTheme === 'light'
            ? savedTheme
            : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = theme;
    })();
</script>

<style>
    html[data-theme="dark"] { color-scheme: dark; }
    html[data-theme="dark"] body {
        background-color: #0f172a !important;
        background-image: radial-gradient(circle at top, #172554, #0f172a 45%, #020617 100%) !important;
        color: #e2e8f0 !important;
    }
    html[data-theme="dark"] main [class*="bg-white"],
    html[data-theme="dark"] main [class*="bg-slate-50"],
    html[data-theme="dark"] main [class*="bg-gray-50"],
    html[data-theme="dark"] main [class*="from-emerald-50"],
    html[data-theme="dark"] main [class*="from-sky-50"],
    html[data-theme="dark"] main [class*="from-violet-50"],
    html[data-theme="dark"] main [class*="from-amber-50"] {
        background-color: #111c30 !important;
        background-image: none !important;
    }
    html[data-theme="dark"] main [class*="text-slate-9"],
    html[data-theme="dark"] main [class*="text-slate-8"],
    html[data-theme="dark"] main [class*="text-gray-9"],
    html[data-theme="dark"] main [class*="text-gray-8"] {
        color: #f8fafc !important;
    }
    html[data-theme="dark"] main [class*="text-slate-7"],
    html[data-theme="dark"] main [class*="text-slate-6"],
    html[data-theme="dark"] main [class*="text-gray-7"],
    html[data-theme="dark"] main [class*="text-gray-6"] {
        color: #cbd5e1 !important;
    }
    html[data-theme="dark"] main [class*="text-slate-5"],
    html[data-theme="dark"] main [class*="text-slate-4"],
    html[data-theme="dark"] main [class*="text-gray-5"],
    html[data-theme="dark"] main [class*="text-gray-4"] {
        color: #94a3b8 !important;
    }
    html[data-theme="dark"] main [class*="border-slate-"],
    html[data-theme="dark"] main [class*="border-gray-"],
    html[data-theme="dark"] main [class*="border-white"] {
        border-color: #334155 !important;
    }
    html[data-theme="dark"] main input,
    html[data-theme="dark"] main select,
    html[data-theme="dark"] main textarea,
    html[data-theme="dark"] main dialog {
        background-color: #172033 !important;
        border-color: #475569 !important;
        color: #f8fafc !important;
    }
    html[data-theme="dark"] main table thead {
        background-color: #172033 !important;
    }
    html[data-theme="dark"] main table tbody,
    html[data-theme="dark"] main table tr {
        background-color: #111c30 !important;
    }
    html[data-theme="dark"] main .shadow-sm,
    html[data-theme="dark"] main [class*="shadow-"] {
        --tw-shadow-color: rgba(2, 6, 23, .45) !important;
    }
</style>

<script>
    (() => {
        if (window.hrisThemeReady) return;
        window.hrisThemeReady = true;

        const applyTheme = (theme) => {
            const nextTheme = theme === 'dark' ? 'dark' : 'light';
            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem('hris-color-theme', nextTheme);
            document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
                const isDark = nextTheme === 'dark';
                toggle.setAttribute('aria-checked', String(isDark));
                toggle.dataset.activeTheme = nextTheme;
                const label = toggle.querySelector('[data-theme-label]');
                if (label) label.textContent = isDark ? 'Dark mode' : 'Light mode';
            });
            window.dispatchEvent(new CustomEvent('hris:theme-changed', { detail: { theme: nextTheme } }));
        };

        window.setHrisTheme = applyTheme;
        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-theme-toggle]');
            if (!toggle) return;
            applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
        });
        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(document.documentElement.dataset.theme || 'light');
        }, { once: true });
    })();
</script>
