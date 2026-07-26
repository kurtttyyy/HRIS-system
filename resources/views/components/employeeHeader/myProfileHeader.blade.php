<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 px-4 py-4 backdrop-blur-xl md:px-8 md:py-5">
    <div class="flex items-center justify-between gap-4 rounded-[1.75rem] border border-sky-100 bg-gradient-to-r from-white via-sky-50 to-white px-5 py-5 shadow-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Profile Workspace</p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900">My Profile</h2>
            <p class="mt-1 text-sm text-slate-500">View and manage your personal information</p>
        </div>

        <div class="flex items-center gap-2">
            @include('components.themeToggle')
            <a href="{{ route('employee.employeeNotifications') }}" class="relative cursor-pointer rounded-2xl border border-sky-100 bg-white p-3.5 text-sky-700 transition hover:bg-sky-50" aria-label="Open notifications">
                <span data-employee-notification-badge data-fallback-count="0" class="hidden pointer-events-none absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white">
                    0
                </span>
                <i class="pointer-events-none fa fa-bell fa-lg"></i>
            </a>

            <div class="relative group">
                <button class="flex h-11 w-11 items-center justify-center rounded-2xl border border-sky-100 bg-white text-sky-700 transition hover:bg-sky-50">
                    <i class="fa fa-user text-lg"></i>
                </button>

                <div class="absolute right-0 z-50 mt-3 invisible w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg opacity-0 transition-all duration-200 group-hover:visible group-hover:opacity-100">
                    <a href="{{ route('employee.employeeProfile', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fa fa-user"></i>
                        My Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        @if (request()->filled('tab_session'))
                            <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
                        @endif
                        <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50">
                            <i class="fa fa-sign-out"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<script>
    (function () {
        const badge = document.querySelector('[data-employee-notification-badge]');
        if (!badge) return;
        const summaryUrl = @json(route('employee.employeeNotifications.summary'));
        const readKey = 'employee_notifications_read_v1';
        const unreadKey = 'employee_notifications_unread_v1';
        const fallbackCount = Number.parseInt(badge.getAttribute('data-fallback-count') || '0', 10) || 0;
        const renderBadge = (count) => {
            if (count > 0) {
                badge.textContent = String(count);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        };
        const computeUnreadCount = (items) => {
            let readLookup = {};
            try {
                const raw = localStorage.getItem(readKey);
                const parsed = raw ? JSON.parse(raw) : {};
                readLookup = parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                readLookup = {};
            }
            return (Array.isArray(items) ? items : []).reduce((count, item) => {
                const id = item?.id ? String(item.id) : '';
                return count + (id && !readLookup[id] ? 1 : 0);
            }, 0);
        };
        const syncBadge = async () => {
            try {
                const response = await fetch(summaryUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('Unable to load notification summary.');
                const payload = await response.json();
                const unreadCount = computeUnreadCount(payload?.items ?? []);
                localStorage.setItem(unreadKey, String(unreadCount));
                renderBadge(unreadCount);
            } catch (error) {
                const storedUnread = Number.parseInt(localStorage.getItem(unreadKey) || '', 10);
                renderBadge(Number.isFinite(storedUnread) ? storedUnread : fallbackCount);
            }
        };
        syncBadge();
        window.addEventListener('storage', function (event) {
            if (event.key === unreadKey) {
                const nextCount = Number.parseInt(event.newValue || '', 10);
                renderBadge(Number.isFinite(nextCount) ? nextCount : fallbackCount);
            }
        });
    })();
</script>
