@php
    $employeeUser = auth()->user();
    $showDepartmentHeadMore = strtolower(trim((string) ($employeeUser->department_head ?? ''))) === 'approved';
    $employeeMoreOpen = request()->routeIs('employee.employeeHierarchy') || request()->routeIs('employee.employeeEvaluation');
    $employeeUnreadMessages = 0;
    $employeeMissingDocumentCount = 0;
    $employeeNotificationCount = 0;
    if (
        $employeeUser
        && \Illuminate\Support\Facades\Schema::hasTable('conversations')
        && \Illuminate\Support\Facades\Schema::hasTable('conversation_messages')
    ) {
        $employeeUnreadMessages = \App\Models\ConversationMessage::query()
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', (int) $employeeUser->id)
            ->whereHas('conversation', function ($query) use ($employeeUser) {
                $query->where(function ($innerQuery) use ($employeeUser) {
                    $innerQuery->where('user_one_id', (int) $employeeUser->id)
                        ->orWhere('user_two_id', (int) $employeeUser->id);
                });
            })
            ->count();
    }

    if ($employeeUser && \Illuminate\Support\Facades\Schema::hasTable('applicants') && \Illuminate\Support\Facades\Schema::hasTable('applicant_documents')) {
        $applicant = \App\Models\Applicant::query()
            ->where('user_id', (int) $employeeUser->id)
            ->where('application_status', 'Hired')
            ->latest('id')
            ->first();

        if ($applicant) {
            $requiredPrefix = '__REQUIRED__::';
            $noticeType = '__NOTICE__';
            $folderType = '__FOLDER__';
            $normalizeDocumentLabel = static function (string $value): string {
                $normalized = strtolower(trim($value));
                if ($normalized === '') {
                    return '';
                }
                return preg_replace('/\s+/', ' ', $normalized);
            };
            $accessibleApplicantIds = collect([(int) $applicant->id]);
            $normalizedApplicantEmail = strtolower(trim((string) ($applicant->email ?? '')));
            $previousApplicant = \App\Models\Applicant::query()
                ->where('id', '!=', (int) $applicant->id)
                ->whereRaw("LOWER(TRIM(COALESCE(application_status, ''))) = ?", ['hired'])
                ->where(function ($query) use ($applicant, $normalizedApplicantEmail) {
                    $userId = (int) ($applicant->user_id ?? 0);
                    if ($userId > 0) {
                        $query->orWhere('user_id', $userId);
                    }

                    if ($normalizedApplicantEmail !== '') {
                        $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$normalizedApplicantEmail]);
                    }
                })
                ->orderByDesc('date_hired')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            if ($previousApplicant) {
                $accessibleApplicantIds->push((int) $previousApplicant->id);
            }
            $accessibleApplicantIds = $accessibleApplicantIds
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $requiredConfig = [];
            $metaDocuments = \App\Models\ApplicantDocument::query()
                ->where('applicant_id', (int) $applicant->id)
                ->where(function ($query) use ($requiredPrefix, $noticeType) {
                    $query
                        ->where('type', 'like', $requiredPrefix.'%')
                        ->orWhere('type', $noticeType);
                })
                ->orderByDesc('id')
                ->get();

            if ($metaDocuments->isNotEmpty()) {
                $requiredDocuments = $metaDocuments
                    ->filter(fn ($doc) => str_starts_with((string) ($doc->type ?? ''), $requiredPrefix))
                    ->map(function ($doc) use ($requiredPrefix) {
                        return trim((string) substr((string) $doc->type, strlen($requiredPrefix)));
                    })
                    ->filter()
                    ->unique(fn ($value) => strtolower((string) $value))
                    ->values()
                    ->all();

                $requiredConfig = [
                    'required_documents' => $requiredDocuments,
                    'document_notice' => (string) optional($metaDocuments->firstWhere('type', $noticeType))->filename,
                ];
            } else {
                $disk = \Illuminate\Support\Facades\Storage::disk('local');
                $path = 'required_employee_documents.json';
                if ($disk->exists($path)) {
                    $payload = json_decode((string) $disk->get($path), true);
                    $applicants = is_array($payload['applicants'] ?? null) ? $payload['applicants'] : [];
                    $requiredConfig = is_array($applicants[(string) $applicant->id] ?? null) ? $applicants[(string) $applicant->id] : [];
                }
            }

            $requiredDocuments = collect($requiredConfig['required_documents'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values();

            if ($requiredDocuments->isEmpty()) {
                $requiredDocuments = collect([
                    'Resume/CV',
                    'Cover Letter',
                    'Personal Data Sheet (PDS)',
                    'Transcript Of Records',
                    'Diploma',
                    'PRC License/Board Rating',
                    'Certificate Of Eligibility / Certificate of Passing',
                    'Certifications & Supporting Document',
                    'Membership/Affiliation',
                ]);
            }

            $uploadedDocumentTypesNormalized = \App\Models\ApplicantDocument::query()
                ->whereIn('applicant_id', $accessibleApplicantIds)
                ->where('type', 'not like', $requiredPrefix.'%')
                ->where('type', '!=', $noticeType)
                ->where('type', '!=', $folderType)
                ->get()
                ->map(function ($doc) use ($normalizeDocumentLabel) {
                    return $normalizeDocumentLabel((string) ($doc->type ?: $doc->filename));
                })
                ->filter()
                ->unique()
                ->values();

            $employeeMissingDocumentCount = (int) $requiredDocuments
                ->filter(function ($required) use ($uploadedDocumentTypesNormalized, $normalizeDocumentLabel) {
                    return !$uploadedDocumentTypesNormalized->contains($normalizeDocumentLabel((string) $required));
                })
                ->count();
        }
    }

    if ($employeeUser) {
        $employeeId = trim((string) ($employeeUser->employee->employee_id ?? ''));

        $leaveNotificationsCount = \Illuminate\Support\Facades\Schema::hasTable('leave_applications')
            ? \App\Models\LeaveApplication::query()->where('user_id', (int) $employeeUser->id)->limit(5)->count()
            : 0;

        $payslipNotificationsCount = \Illuminate\Support\Facades\Schema::hasTable('payslip_records')
            ? \App\Models\PayslipRecord::query()
                ->where(function ($query) use ($employeeUser, $employeeId) {
                    $query->where('user_id', (int) $employeeUser->id);
                    if ($employeeId !== '') {
                        $query->orWhere('employee_id', $employeeId);
                    }
                })
                ->limit(4)
                ->count()
            : 0;

        $attendanceNotificationsCount = 0;

        $resignationNotificationsCount = \Illuminate\Support\Facades\Schema::hasTable('resignations')
            ? \App\Models\Resignation::query()->where('user_id', (int) $employeeUser->id)->limit(3)->count()
            : 0;

        $employeeNotificationCount = (int) (
            $leaveNotificationsCount
            + $payslipNotificationsCount
            + $attendanceNotificationsCount
            + $resignationNotificationsCount
        );
    }
@endphp

<button
    type="button"
    data-employee-sidebar-toggle
    class="fixed left-4 top-4 z-[70] inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-200 bg-white/95 text-emerald-700 shadow-lg shadow-slate-900/10 backdrop-blur"
    aria-label="Open employee menu"
    aria-controls="employee-sidebar"
    aria-expanded="false"
>
    <i class="fa fa-bars text-lg"></i>
</button>

<div data-employee-sidebar-overlay class="employee-sidebar-overlay"></div>

<aside
    id="employee-sidebar"
    class="employee-sidebar group fixed left-0 top-0 h-screen bg-gray-900 border-r border-gray-700
           w-16 hover:w-56 transition-all duration-300 overflow-hidden z-50"
>

    <!-- Logo -->
    <div class="p-4 border-b border-gray-700">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 flex items-center justify-center flex-shrink-0">
                <img
                    src="{{ asset('images/logo.webp') }}"
                    alt="Logo"
                    class="w-10 h-10 object-contain block"
                >
            </div>

            <!-- Logo text -->
            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                <h1 class="text-sm font-bold text-white">
                    Northeastern College
                </h1>
                <p class="text-xs text-gray-400">
                    Employee Portal
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="p-2 space-y-2">
        <!-- Main -->
        <div class="hidden px-4 pt-2 text-[10px] font-bold uppercase tracking-[0.22em] text-gray-500 opacity-0 transition-opacity duration-300 group-hover:block group-hover:opacity-100 employee-sidebar-label">
            Main
        </div>

        <!-- Dashboard -->
        <a href="{{ route('employee.employeeHome') }}"
           data-employee-nav
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition
                  {{ request()->routeIs('employee.employeeHome')
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

            <span class="relative inline-flex items-center justify-center w-6">
                <i class="fa fa-dashboard text-lg text-center"></i>
                @if ($employeeNotificationCount > 0)
                    <span class="absolute -right-2 -top-2 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold leading-none text-white group-hover:hidden">{{ $employeeNotificationCount > 9 ? '9+' : $employeeNotificationCount }}</span>
                @endif
            </span>

            <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                Dashboard
            </span>
            @if ($employeeNotificationCount > 0)
                <span class="ml-auto hidden min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white group-hover:inline-flex">{{ $employeeNotificationCount > 99 ? '99+' : $employeeNotificationCount }}</span>
            @endif
        </a>

        <!-- Leave Requests -->
        <a href="{{ route('employee.employeeLeave') }}"
           data-employee-nav
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition
                  {{ request()->routeIs('employee.employeeLeave')
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

            <span class="relative inline-flex items-center justify-center w-6">
                <i class="fa fa-calendar text-lg text-center"></i>
            </span>

            <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                Leave Requests
            </span>
        </a>

        <!-- Payslips -->
        <a href="{{ route('employee.employeePayslip') }}"
           data-employee-nav
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition
                  {{ request()->routeIs('employee.employeePayslip')
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

            <i class="fa fa-file-text-o text-lg w-6 text-center"></i>

            <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                Payslips
            </span>
        </a>

        <!-- Documents -->
        <a href="{{ route('employee.employeeDocument') }}"
           data-employee-nav
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition
                  {{ request()->routeIs('employee.employeeDocument')
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

            <span class="relative inline-flex items-center justify-center w-6">
                <i class="fa fa-folder text-lg text-center"></i>
                @if ($employeeMissingDocumentCount > 0)
                    <span class="absolute -right-2 -top-2 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold leading-none text-white group-hover:hidden">{{ $employeeMissingDocumentCount > 9 ? '9+' : $employeeMissingDocumentCount }}</span>
                @endif
            </span>

            <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                Documents
            </span>
            @if ($employeeMissingDocumentCount > 0)
                <span class="ml-auto hidden min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white group-hover:inline-flex">{{ $employeeMissingDocumentCount > 99 ? '99+' : $employeeMissingDocumentCount }}</span>
            @endif
        </a>

        <!-- Communication -->
        <a href="{{ route('employee.employeeCommunication') }}"
           data-employee-nav
           data-employee-communication-nav
           class="relative flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition
                  {{ request()->routeIs('employee.employeeCommunication')
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

            <span class="relative inline-flex items-center justify-center w-6">
                <i class="fa fa-users text-lg text-center"></i>
                @if ($employeeUnreadMessages > 0)
                    <span class="absolute -right-2 -top-2 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold leading-none text-white group-hover:hidden">{{ $employeeUnreadMessages > 9 ? '9+' : $employeeUnreadMessages }}</span>
                @endif
            </span>

            <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                Communication
            </span>
            @if ($employeeUnreadMessages > 0)
                <span class="ml-auto hidden min-w-[1.4rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white group-hover:inline-flex">{{ $employeeUnreadMessages > 99 ? '99+' : $employeeUnreadMessages }}</span>
            @endif
        </a>

        <!-- Resignation -->
        <a href="{{ route('employee.employeeResignation') }}"
           data-employee-nav
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition
                  {{ request()->routeIs('employee.employeeResignation')
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

            <i class="fa fa-user-times text-lg w-6 text-center"></i>

                <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                Resignation
            </span>
        </a>

        <!-- Help -->
        <div class="hidden px-4 pt-2 text-[10px] font-bold uppercase tracking-[0.22em] text-gray-500 opacity-0 transition-opacity duration-300 group-hover:block group-hover:opacity-100 employee-sidebar-label">
            Help
        </div>

        <!-- Staff Guide -->
        <a href="{{ route('employee.employeeStaffGuide') }}"
           data-employee-nav
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition
                  {{ request()->routeIs('employee.employeeStaffGuide')
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

            <i class="fa fa-book text-lg w-6 text-center"></i>

            <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                Staff Guide
            </span>
        </a>

        @if ($showDepartmentHeadMore)
            <div class="space-y-1">
                <button type="button"
                    data-employee-more-toggle
                    aria-expanded="{{ $employeeMoreOpen ? 'true' : 'false' }}"
                    class="flex w-full items-center gap-3 px-4 py-2.5 rounded-lg font-medium transition {{ $employeeMoreOpen ? 'bg-green-600/20 text-white' : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">

                    <i class="fa fa-ellipsis-h text-lg w-6 text-center"></i>

                    <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        More
                    </span>

                    <i class="fa fa-chevron-down employee-more-icon ml-auto text-xs transition-all duration-300 {{ $employeeMoreOpen ? 'is-open opacity-100' : 'opacity-0 group-hover:opacity-100' }}"
                       data-employee-more-icon></i>
                </button>

                <div class="{{ $employeeMoreOpen ? '' : 'hidden' }} space-y-1 pl-3"
                     data-employee-more-menu>
                    <a href="{{ route('employee.employeeHierarchy') }}"
                       data-employee-nav
                       class="flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('employee.employeeHierarchy') ? 'bg-green-600 text-white hover:bg-green-700' : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">
                        <i class="fa fa-building-o text-base w-6 text-center"></i>
                        <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                            Employee Hierarchy
                        </span>
                    </a>

                    <a href="{{ route('employee.employeeEvaluation') }}"
                       data-employee-nav
                       class="flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('employee.employeeEvaluation') ? 'bg-green-600 text-white hover:bg-green-700' : 'text-gray-300 hover:bg-green-600/20 hover:text-white' }}">
                        <i class="fa fa-line-chart text-base w-6 text-center"></i>
                        <span class="whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 employee-sidebar-label">
                            Evaluation
                        </span>
                    </a>
                </div>
            </div>
        @endif

    </nav>

</aside>

<!-- Font Awesome -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<style>
    @media (min-width: 1025px) {
        .employee-sidebar:not(:hover) > div:first-child {
            padding-left: 4.9rem;
            padding-right: 0.5rem;
        }

        .employee-sidebar:not(:hover) > div:first-child > div {
            justify-content: center;
        }

        .employee-sidebar:not(:hover) > nav > a,
        .employee-sidebar:not(:hover) > nav > div > button {
            justify-content: center;
            gap: 0;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .employee-sidebar:not(:hover) > nav > a > .employee-sidebar-label,
        .employee-sidebar:not(:hover) > nav > div > button > span:not(.relative),
        .employee-sidebar:not(:hover) > nav > div > button > .employee-more-icon {
            display: none !important;
        }

        .employee-sidebar:not(:hover) [data-employee-more-menu] {
            display: none !important;
        }

        .employee-sidebar:not(:hover) nav .ml-auto {
            margin-left: 0 !important;
        }
    }

    .employee-more-icon.is-open {
        transform: rotate(180deg);
    }

    .employee-sidebar-overlay {
        position: fixed;
        inset: 0;
        z-index: 45;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
        opacity: 0;
        pointer-events: none;
        transition: opacity 180ms ease;
    }

    .employee-sidebar-overlay.is-visible {
        opacity: 1;
        pointer-events: auto;
    }

    @media (max-width: 1024px) {
        .employee-sidebar {
            width: 16rem !important;
            transform: translateX(-100%);
            transition: transform 220ms ease;
        }

        .employee-sidebar.is-open {
            transform: translateX(0);
        }

        .employee-sidebar ~ main {
            margin-left: 0 !important;
            width: 100%;
        }

        .employee-sidebar .employee-sidebar-label {
            opacity: 1 !important;
            max-width: 100% !important;
        }
    }

    @media (min-width: 1025px) {
        [data-employee-sidebar-toggle],
        [data-employee-sidebar-overlay] {
            display: none !important;
        }

        .employee-sidebar {
            transform: none !important;
        }
    }

    .employee-nav-overlay {
        position: fixed;
        inset: 0;
        z-index: 70;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(2, 6, 23, 0.22);
        backdrop-filter: blur(6px);
        opacity: 0;
        pointer-events: none;
        transition: opacity 180ms ease;
    }

    .employee-nav-overlay.is-visible {
        opacity: 1;
        pointer-events: auto;
    }

    .employee-nav-overlay__card {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.95rem 1.1rem;
        border-radius: 9999px;
        background: rgba(15, 23, 42, 0.92);
        color: #f8fafc;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.25);
        transform: translateY(8px) scale(0.98);
        transition: transform 220ms ease;
    }

    .employee-nav-overlay.is-visible .employee-nav-overlay__card {
        transform: translateY(0) scale(1);
    }

    .employee-nav-overlay__spinner {
        width: 1rem;
        height: 1rem;
        border-radius: 9999px;
        border: 2px solid rgba(255, 255, 255, 0.28);
        border-top-color: #4ade80;
        animation: employee-nav-spin 0.75s linear infinite;
    }

    @keyframes employee-nav-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<script>
    (function () {
        const links = Array.from(document.querySelectorAll('[data-employee-nav]'));
        const sidebar = document.querySelector('.employee-sidebar');
        const sidebarToggle = document.querySelector('[data-employee-sidebar-toggle]');
        const sidebarOverlay = document.querySelector('[data-employee-sidebar-overlay]');
        const moreToggle = document.querySelector('[data-employee-more-toggle]');
        const moreMenu = document.querySelector('[data-employee-more-menu]');
        const moreIcon = document.querySelector('[data-employee-more-icon]');
        const currentUrl = new URL(window.location.href);
        const tabSession = currentUrl.searchParams.get('tab_session') || '';
        if (!links.length && !tabSession && !moreToggle) {
            return;
        }

        let overlay = document.querySelector('[data-employee-nav-overlay]');
        const prefetched = new Set();

        const appendTabSession = (href) => {
            if (!href || !tabSession) {
                return href;
            }

            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) {
                return href;
            }

            url.searchParams.set('tab_session', tabSession);
            return `${url.pathname}${url.search}${url.hash}`;
        };

        if (tabSession) {
            document.querySelectorAll('a[href]').forEach((anchor) => {
                const href = anchor.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                    return;
                }

                const nextHref = appendTabSession(href);
                if (nextHref) {
                    anchor.setAttribute('href', nextHref);
                }
            });

            document.querySelectorAll('form').forEach((form) => {
                let hiddenInput = form.querySelector('input[name="tab_session"]');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'tab_session';
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = tabSession;
            });
        }

        const ensureOverlay = () => {
            if (overlay) {
                return overlay;
            }

            overlay = document.createElement('div');
            overlay.className = 'employee-nav-overlay';
            overlay.setAttribute('data-employee-nav-overlay', '');
            overlay.innerHTML = `
                <div class="employee-nav-overlay__card">
                    <span class="employee-nav-overlay__spinner"></span>
                    <span class="text-sm font-semibold tracking-wide">Opening page...</span>
                </div>
            `;
            document.body.appendChild(overlay);
            return overlay;
        };

        const prefetchPage = (href) => {
            if (!href || prefetched.has(href)) {
                return;
            }

            prefetched.add(href);
            fetch(href, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Employee-Prefetch': '1',
                },
            }).catch(() => {
            });
        };

        const bindNavigationLink = (link) => {
            if (link.dataset.employeeNavBound === 'true') {
                return;
            }
            link.dataset.employeeNavBound = 'true';

            const href = appendTabSession(link.getAttribute('href'));
            if (!href) {
                return;
            }

            link.setAttribute('href', href);

            link.addEventListener('mouseenter', () => prefetchPage(href), { passive: true });
            link.addEventListener('focus', () => prefetchPage(href), { passive: true });

            link.addEventListener('click', (event) => {
                if (
                    event.defaultPrevented
                    || event.metaKey
                    || event.ctrlKey
                    || event.shiftKey
                    || event.altKey
                    || link.target === '_blank'
                ) {
                    return;
                }

                const latestUrl = new URL(window.location.href);
                const nextUrl = new URL(href, window.location.origin);
                if (latestUrl.pathname === nextUrl.pathname && latestUrl.search === nextUrl.search) {
                    if (nextUrl.hash && nextUrl.hash !== latestUrl.hash) {
                        const target = document.querySelector(nextUrl.hash);
                        if (target) {
                            event.preventDefault();
                            const activeOverlay = ensureOverlay();
                            activeOverlay.classList.add('is-visible');
                            window.setTimeout(() => {
                                activeOverlay.classList.remove('is-visible');
                                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                history.pushState({}, '', nextUrl.hash);
                            }, 220);
                        }
                    }
                    return;
                }

                ensureOverlay().classList.add('is-visible');
            });
        };

        links.forEach(bindNavigationLink);
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-employee-nav]').forEach(bindNavigationLink);
        }, { once: true });

        window.addEventListener('pageshow', () => {
            if (overlay) {
                overlay.classList.remove('is-visible');
            }
        });

        const isCompactViewport = () => window.matchMedia('(max-width: 1024px)').matches;

        const closeSidebar = () => {
            if (!sidebar || !sidebarOverlay || !sidebarToggle) {
                return;
            }

            sidebar.classList.remove('is-open');
            sidebarOverlay.classList.remove('is-visible');
            sidebarToggle.setAttribute('aria-expanded', 'false');
        };

        const openSidebar = () => {
            if (!sidebar || !sidebarOverlay || !sidebarToggle) {
                return;
            }

            sidebar.classList.add('is-open');
            sidebarOverlay.classList.add('is-visible');
            sidebarToggle.setAttribute('aria-expanded', 'true');
        };

        if (sidebarToggle && sidebar && sidebarOverlay) {
            sidebarToggle.addEventListener('click', () => {
                if (!isCompactViewport()) {
                    return;
                }

                const isOpen = sidebar.classList.contains('is-open');
                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            sidebarOverlay.addEventListener('click', closeSidebar);

            window.addEventListener('resize', () => {
                if (!isCompactViewport()) {
                    closeSidebar();
                }
            });
        }

        if (moreToggle && moreMenu) {
            moreToggle.addEventListener('click', () => {
                const isOpen = !moreMenu.classList.contains('hidden');
                moreMenu.classList.toggle('hidden', isOpen);
                moreToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                if (moreIcon) {
                    moreIcon.classList.toggle('is-open', !isOpen);
                }
            });
        }

        links.forEach((link) => {
            link.addEventListener('click', () => {
                if (isCompactViewport()) {
                    closeSidebar();
                }
            });
        });
    })();
</script>

@include('components.idleSessionTimeout')
