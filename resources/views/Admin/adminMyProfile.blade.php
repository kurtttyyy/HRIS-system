<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body{font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
        main{margin-left:4rem;transition:margin-left .3s ease}
        aside:hover~main{margin-left:18rem}
        .profile-card{transition:transform .24s ease,border-color .24s ease,box-shadow .24s ease}
        .profile-card:hover{transform:translateY(-3px);border-color:#a7f3d0;box-shadow:0 18px 40px rgba(15,23,42,.08)}
        .profile-reveal{animation:profile-rise .55s cubic-bezier(.22,1,.36,1) both;animation-delay:var(--delay,0ms)}
        dialog::backdrop{background:rgba(15,23,42,.62);backdrop-filter:blur(5px)}
        html[data-theme="dark"] :is(#create-admin-account,#edit-admin-account) .admin-permission-panel{
            background:#111c30!important;
            border-color:#33445b!important;
        }
        html[data-theme="dark"] :is(#create-admin-account,#edit-admin-account) .admin-permission-panel>p{
            color:#6ee7b7!important;
        }
        html[data-theme="dark"] :is(#create-admin-account,#edit-admin-account) .admin-permission-option{
            background:#162238!important;
            border-color:#34455c!important;
            color:#cbd5e1!important;
            transition:border-color .18s ease,background-color .18s ease;
        }
        html[data-theme="dark"] :is(#create-admin-account,#edit-admin-account) .admin-permission-option:hover{
            background:#1a2940!important;
            border-color:#4b647e!important;
        }
        html[data-theme="dark"] :is(#create-admin-account,#edit-admin-account) .admin-permission-option:has(input:checked){
            background:#12302d!important;
            border-color:#2f806d!important;
            color:#e2e8f0!important;
        }
        html[data-theme="dark"] :is(#create-admin-account,#edit-admin-account)>form>div:nth-child(2){
            scrollbar-color:#526078 #111827;
        }
        html[data-theme="dark"] main .admin-modal-cancel:hover{
            background:#243147!important;
            color:#e2e8f0!important;
            filter:none!important;
            transform:none!important;
            box-shadow:none!important;
        }
        @keyframes profile-rise{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
        @media(prefers-reduced-motion:reduce){.profile-reveal{animation:none}.profile-card{transition:none}.profile-card:hover{transform:none}}
        @media(max-width:767px){
            .admin-profile-content{padding:.5rem .75rem 1rem!important}
            .admin-profile-hero{border:1px solid #e2e8f0;background:#fff!important;color:#0f172a!important;border-radius:1.25rem!important;box-shadow:none!important}
            .admin-profile-hero>.absolute{display:none}
            .admin-profile-hero-inner{gap:.75rem!important;padding:1rem!important}
            .admin-profile-identity{display:grid!important;grid-template-columns:3rem minmax(0,1fr);align-items:center;gap:.75rem!important}
            .admin-profile-avatar{width:3rem!important;height:3rem!important;border-radius:.85rem!important;border:0!important;font-size:1rem!important;box-shadow:none!important;--tw-ring-shadow:none!important}
            .admin-profile-avatar+span{width:.8rem!important;height:.8rem!important;border-width:2px!important;border-color:#fff!important}
            .admin-profile-name-row{gap:.4rem!important}
            .admin-profile-name{font-size:1.1rem!important;line-height:1.25!important}
            .admin-profile-status{padding:.2rem .45rem!important;background:#ecfdf5!important;color:#047857!important;font-size:.58rem!important;box-shadow:none!important}
            .admin-profile-role{margin-top:.25rem!important;color:#047857!important;font-size:.72rem!important}
            .admin-profile-badges{display:none!important}
            .admin-profile-actions{grid-column:1/-1;margin-top:.25rem!important;gap:.5rem!important}
            .admin-profile-actions button{justify-content:center;padding:.5rem .65rem!important;border:1px solid #d1fae5!important;background:#ecfdf5!important;color:#047857!important;box-shadow:none!important;font-size:.68rem!important}
            .admin-profile-actions button:nth-child(n+2){display:none!important}
            .admin-profile-stats{display:none!important}
            .admin-profile-information{padding:1rem!important;border-radius:1.25rem!important;box-shadow:none!important}
            .admin-profile-information-heading{gap:.5rem!important}
            .admin-profile-information-heading>div:first-child{padding-left:3.25rem}
            .admin-profile-information-heading h2{margin-top:.25rem!important;font-size:1.15rem!important;line-height:1.25!important}
            .admin-profile-information-heading p:first-child{font-size:.55rem!important;letter-spacing:.12em!important}
            .admin-profile-information-heading p:last-child{margin-top:.2rem!important;font-size:.68rem!important;line-height:1.4!important}
            .admin-profile-information-heading>span{padding:.25rem .5rem!important;font-size:.58rem!important}
            .admin-profile-details{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:.5rem!important;margin-top:.75rem!important}
            .admin-profile-detail{min-width:0;padding:.65rem!important;border-radius:.85rem!important}
            .admin-profile-detail:first-child{grid-column:1/-1}
            .admin-profile-detail>div:first-child{width:2rem!important;height:2rem!important;border-radius:.6rem!important;font-size:.75rem!important}
            .admin-profile-detail>p:nth-child(2){margin-top:.45rem!important;font-size:.5rem!important;letter-spacing:.1em!important}
            .admin-profile-detail>p:last-child{margin-top:.15rem!important;font-size:.7rem!important;line-height:1.35!important;overflow-wrap:anywhere}
            .admin-profile-side{gap:.75rem!important}
            .admin-profile-security,.admin-profile-quick-access,.admin-profile-team{padding:1rem!important;border-radius:1.25rem!important;box-shadow:none!important}
            .admin-profile-security>div:first-child>div{display:none!important}
            .admin-profile-security>div:first-child>span{margin-left:auto;padding:.2rem .45rem!important;font-size:.5rem!important}
            .admin-profile-security h3{margin-top:.35rem!important;font-size:.9rem!important}
            .admin-profile-security>p{margin-top:.25rem!important;font-size:.65rem!important;line-height:1.4!important}
            .admin-profile-security>a{margin-top:.65rem!important;padding:.6rem!important;border-radius:.75rem!important;font-size:.68rem!important;box-shadow:none!important}
            .admin-profile-quick-access>p{font-size:.55rem!important;letter-spacing:.12em!important}
            .admin-profile-quick-links{margin-top:.5rem!important;display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr));gap:.4rem!important}
            .admin-profile-quick-links a{justify-content:center!important;padding:.6rem .35rem!important;border:1px solid #e2e8f0!important;border-radius:.75rem!important;font-size:.62rem!important;text-align:center}
            .admin-profile-quick-links a span i{display:block;margin:0 auto .25rem!important}
            .admin-profile-quick-links a>i{display:none}
            .admin-profile-team-heading{gap:.65rem!important}
            .admin-profile-team-heading>div p:first-child{font-size:.55rem!important;letter-spacing:.12em!important}
            .admin-profile-team-heading h2{margin-top:.25rem!important;font-size:1.15rem!important}
            .admin-profile-team-heading>div p:last-child{margin-top:.2rem!important;font-size:.68rem!important;line-height:1.4!important}
            .admin-profile-team-heading>button{padding:.55rem .7rem!important;border-radius:.75rem!important;font-size:.65rem!important}
            .admin-profile-team-grid{margin-top:.75rem!important;gap:.5rem!important}
            .admin-profile-team-grid .admin-account-card{gap:.65rem!important;padding:.65rem!important;border-radius:.85rem!important}
            .admin-profile-team-grid .admin-account-card>div:first-child{width:2.25rem!important;height:2.25rem!important;border-radius:.65rem!important;font-size:.68rem!important}
        }
    </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#ecfdf5,_#eff6ff_38%,_#f8fafc_75%)] text-slate-900">
@php
    $fullName = trim(implode(' ', array_filter([$admin->first_name, $admin->middle_name, $admin->last_name])));
    $fullName = $fullName !== '' ? $fullName : 'Administrator';
    $initials = strtoupper(substr((string) $admin->first_name, 0, 1).substr((string) $admin->last_name, 0, 1));
    $initials = $initials !== '' ? $initials : 'AD';
    $status = trim((string) ($admin->account_status ?: $admin->status ?: 'Active'));
    $details = [
        ['icon' => 'fa-envelope', 'label' => 'Email address', 'value' => $admin->email],
        ['icon' => 'fa-user-shield', 'label' => 'Account role', 'value' => ucfirst((string) $admin->role)],
        ['icon' => 'fa-briefcase', 'label' => 'Position', 'value' => $admin->job_role ?: $admin->position],
        ['icon' => 'fa-building', 'label' => 'Department', 'value' => $admin->department],
        ['icon' => 'fa-phone', 'label' => 'Contact number', 'value' => data_get($admin, 'employee.contact_number')],
        ['icon' => 'fa-calendar-plus', 'label' => 'Account created', 'value' => optional($admin->created_at)->format('F j, Y')],
    ];
    $completedDetails = collect($details)->filter(fn ($detail) => trim((string) $detail['value']) !== '')->count();
    $profileCompletion = (int) round(($completedDetails / max(count($details), 1)) * 100);
@endphp
<div class="flex min-h-screen">
    @include('components.adminSideBar')
    <main class="min-w-0 flex-1">
        @include('components.adminHeader.dashboardHeader', [
            'headerTitle' => 'My Profile',
            'headerSubtitle' => 'Review your administrator identity and account information.',
            'showThemeToggle' => true,
            'profileMobileHeader' => true,
        ])

        <div class="admin-profile-content mx-auto max-w-[1450px] space-y-6 p-4 pt-20 md:p-8">
            @if(session('success'))
                <div class="profile-reveal rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700"><i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="profile-reveal rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700"><p class="font-bold">Your changes could not be saved.</p><p class="mt-1">{{ $errors->first() }}</p></div>
            @endif
            <section class="admin-profile-hero profile-reveal relative overflow-hidden rounded-[2.25rem] bg-slate-950 text-white shadow-[0_30px_90px_rgba(15,23,42,.22)]" style="--delay:0ms">
                <div class="absolute -right-24 -top-28 h-80 w-80 rounded-full bg-emerald-500/25 blur-3xl"></div>
                <div class="absolute bottom-0 left-1/3 h-52 w-52 rounded-full bg-cyan-500/15 blur-3xl"></div>
                <div class="admin-profile-hero-inner relative grid gap-8 px-7 py-9 md:px-10 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="admin-profile-identity flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="relative">
                            <div class="admin-profile-avatar flex h-28 w-28 shrink-0 items-center justify-center rounded-[2rem] border border-white/40 bg-gradient-to-br from-emerald-400 to-cyan-500 text-3xl font-black shadow-[0_18px_50px_rgba(16,185,129,.3)] ring-4 ring-white/10">{{ $initials }}</div>
                            <span class="absolute -bottom-1 -right-1 h-7 w-7 rounded-full border-4 border-slate-950 bg-emerald-400"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="admin-profile-name-row flex flex-wrap items-center gap-3">
                                <h1 class="admin-profile-name text-3xl font-black tracking-tight md:text-4xl">{{ $fullName }}</h1>
                                <span class="admin-profile-status inline-flex items-center gap-2 rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-200 ring-1 ring-emerald-300/25"><span class="h-2 w-2 rounded-full bg-emerald-300"></span>{{ $status }}</span>
                            </div>
                            <p class="admin-profile-role mt-2 text-base font-semibold text-emerald-200">{{ $admin->job_role ?: 'System Administrator' }}</p>
                            <div class="admin-profile-badges mt-4 flex flex-wrap gap-2 text-xs text-white/70">
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5"><i class="fa-solid fa-shield-halved mr-1.5 text-emerald-300"></i>{{ ucfirst((string) $admin->role) }} access</span>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5"><i class="fa-solid fa-hashtag mr-1.5 text-cyan-300"></i>Account {{ $admin->id }}</span>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5"><i class="fa-solid fa-building mr-1.5 text-sky-300"></i>{{ $admin->department ?: 'No department' }}</span>
                            </div>
                            <div class="admin-profile-actions mt-5 flex flex-wrap gap-3">
                                <button type="button" onclick="document.getElementById('admin-profile-editor').showModal()" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 shadow-lg transition hover:bg-emerald-100"><i class="fa-solid fa-pen-to-square text-emerald-600"></i>Edit Information</button>
                                @if($admin->hasAdminPermission('manage_admins'))<button type="button" onclick="document.getElementById('create-admin-account').showModal()" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/20"><i class="fa-solid fa-user-shield text-emerald-300"></i>Create Admin Account</button>@endif
                            </div>
                        </div>
                    </div>
                    <div class="admin-profile-stats grid grid-cols-2 gap-3 sm:grid-cols-3 lg:w-[27rem]">
                        <div class="rounded-2xl border border-white/10 bg-white/[.07] p-4 backdrop-blur"><p class="text-[10px] font-bold uppercase tracking-[.18em] text-white/50">Profile</p><p class="mt-2 text-2xl font-black">{{ $profileCompletion }}%</p><p class="mt-1 text-xs text-emerald-200">Complete</p></div>
                        <div class="rounded-2xl border border-white/10 bg-white/[.07] p-4 backdrop-blur"><p class="text-[10px] font-bold uppercase tracking-[.18em] text-white/50">Access</p><p class="mt-2 text-lg font-black">Admin</p><p class="mt-1 text-xs text-cyan-200">Full portal</p></div>
                        <div class="col-span-2 rounded-2xl border border-white/10 bg-white/[.07] p-4 backdrop-blur sm:col-span-1"><p class="text-[10px] font-bold uppercase tracking-[.18em] text-white/50">Member since</p><p class="mt-2 text-sm font-black">{{ optional($admin->created_at)->format('M Y') ?: 'Unknown' }}</p><p class="mt-1 text-xs text-sky-200">Verified account</p></div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
                <section class="admin-profile-information profile-reveal rounded-[2rem] border border-white/80 bg-white/90 p-6 shadow-[0_20px_60px_rgba(15,23,42,.08)] backdrop-blur md:p-8" style="--delay:90ms">
                    <div class="admin-profile-information-heading flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div><p class="text-xs font-bold uppercase tracking-[.22em] text-emerald-700">Personal record</p><h2 class="mt-2 text-2xl font-black">Account information</h2><p class="mt-1 text-sm text-slate-500">Your administrator identity and workplace details.</p></div>
                        <span class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500">Updated {{ optional($admin->updated_at)->diffForHumans() ?: 'recently' }}</span>
                    </div>
                    <div class="admin-profile-details mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($details as $detail)
                            <div class="admin-profile-detail profile-card rounded-2xl border border-slate-200 bg-slate-50/80 p-5">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm ring-1 ring-slate-100"><i class="fa-solid {{ $detail['icon'] }}"></i></div>
                                <p class="mt-4 text-[10px] font-bold uppercase tracking-[.18em] text-slate-400">{{ $detail['label'] }}</p>
                                <p class="mt-1 break-words text-sm font-bold text-slate-800">{{ trim((string) $detail['value']) !== '' ? $detail['value'] : 'Not provided' }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="admin-profile-side space-y-6">
                    <section class="admin-profile-security profile-reveal rounded-[2rem] border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm" style="--delay:150ms">
                        <div class="flex items-center justify-between"><div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20"><i class="fa-solid fa-shield-halved"></i></div><span class="rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700">Protected</span></div>
                        <h3 class="mt-5 text-lg font-black">Account security</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Use a unique password and sign out whenever you use a shared device.</p>
                        <a href="{{ route('password.request') }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700"><i class="fa-solid fa-key"></i>Change Password</a>
                    </section>

                    <section class="admin-profile-quick-access profile-reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm" style="--delay:210ms">
                        <p class="text-xs font-bold uppercase tracking-[.2em] text-slate-400">Quick access</p>
                        <div class="admin-profile-quick-links mt-4 space-y-2">
                            @if($admin->hasAdminPermission('dashboard'))<a href="{{ route('admin.adminHome', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700"><span><i class="fa-solid fa-house mr-3 w-4"></i>Dashboard</span><i class="fa-solid fa-arrow-right text-xs"></i></a>@endif
                            @if($admin->hasAdminPermission('communication'))<a href="{{ route('admin.adminCommunication', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700"><span><i class="fa-solid fa-comments mr-3 w-4"></i>Messages</span><i class="fa-solid fa-arrow-right text-xs"></i></a>@endif
                            @if($admin->hasAdminPermission('logs'))<a href="{{ route('admin.activityLogs', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700"><span><i class="fa-solid fa-clock-rotate-left mr-3 w-4"></i>Activity logs</span><i class="fa-solid fa-arrow-right text-xs"></i></a>@endif
                        </div>
                    </section>

                </div>
            </div>

            <section class="admin-profile-team profile-reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm md:p-8" style="--delay:260ms">
                <div class="admin-profile-team-heading flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="text-xs font-bold uppercase tracking-[.22em] text-emerald-700">Access management</p><h2 class="mt-2 text-2xl font-black">Administrator team</h2><p class="mt-1 text-sm text-slate-500">Accounts with permission to enter the admin portal.</p></div>
                    @if($admin->hasAdminPermission('manage_admins'))<button type="button" onclick="document.getElementById('create-admin-account').showModal()" class="inline-flex w-fit items-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700"><i class="fa-solid fa-user-plus"></i>Add Administrator</button>@endif
                </div>
                <div class="admin-profile-team-grid mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($adminAccounts as $account)
                        @php
                            $accountName = trim(implode(' ', array_filter([$account->first_name, $account->middle_name, $account->last_name])));
                            $accountInitials = strtoupper(substr((string) $account->first_name, 0, 1).substr((string) $account->last_name, 0, 1));
                        @endphp
                        <button
                            type="button"
                            class="profile-card admin-account-card flex w-full items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left"
                            data-admin-account="{{ json_encode([
                                'id' => $account->id,
                                'first_name' => $account->first_name,
                                'middle_name' => $account->middle_name === 'N/A' ? '' : $account->middle_name,
                                'last_name' => $account->last_name,
                                'employee_id' => optional($account->employee)->employee_id,
                                'position' => $account->job_role ?: $account->position,
                                'department' => $account->department,
                                'access_level' => is_null($account->admin_permissions) ? 'full' : 'limited',
                                'permissions' => $account->admin_permissions ?? [],
                            ], JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                            aria-label="Edit {{ $accountName ?: 'administrator' }}"
                        >
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-cyan-500 text-sm font-black text-white">{{ $accountInitials ?: 'AD' }}</div>
                            <div class="min-w-0 flex-1"><div class="flex items-center gap-2"><p class="truncate text-sm font-black">{{ $accountName ?: 'Administrator' }}</p>@if((int)$account->id === (int)$admin->id)<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-700">YOU</span>@endif</div><p class="truncate text-xs text-slate-500">ID: {{ optional($account->employee)->employee_id ?: 'Not assigned' }}</p><p class="mt-1 text-[10px] font-semibold text-emerald-700">{{ is_null($account->admin_permissions) ? 'Full access' : count($account->admin_permissions).' modules' }} · {{ $account->account_status ?: 'Active' }}</p></div>
                            <i class="fa-solid fa-pen text-xs text-slate-400"></i>
                        </button>
                    @endforeach
                </div>
            </section>
        </div>

        <dialog id="admin-profile-editor" class="m-auto w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-0 shadow-2xl">
            <form method="POST" action="{{ route('admin.myProfile.update', array_filter(['tab_session' => request()->query('tab_session')])) }}">
                @csrf
                @if(request()->filled('tab_session'))<input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">@endif
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-5">
                    <div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-700">Profile editor</p><h2 class="mt-1 text-xl font-black">Edit your information</h2></div>
                    <button type="button" onclick="document.getElementById('admin-profile-editor').close()" class="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-200 hover:text-slate-900" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="grid max-h-[65vh] gap-4 overflow-y-auto p-6 sm:grid-cols-2">
                    <label class="text-sm font-bold text-slate-700">First name<input name="first_name" value="{{ old('first_name', $admin->first_name) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Middle name<input name="middle_name" value="{{ old('middle_name', $admin->middle_name) }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Last name<input name="last_name" value="{{ old('last_name', $admin->last_name) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Email address<input type="email" name="email" value="{{ old('email', $admin->email) }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Position<input name="job_role" value="{{ old('job_role', $admin->job_role ?: $admin->position) }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Department<input name="department" value="{{ old('department', $admin->department) }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700 sm:col-span-2">Contact number<input name="contact_number" value="{{ old('contact_number', data_get($admin, 'employee.contact_number')) }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100"></label>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-700 sm:col-span-2"><i class="fa-solid fa-circle-info mr-2"></i>Your administrator role and account creation date cannot be edited here.</div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                    <button type="button" onclick="document.getElementById('admin-profile-editor').close()" class="admin-modal-cancel rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700"><i class="fa-solid fa-floppy-disk"></i>Save Changes</button>
                </div>
            </form>
        </dialog>
        <dialog id="create-admin-account" class="m-auto w-[calc(100vw-2rem)] max-w-2xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-0 shadow-2xl">
            <form method="POST" action="{{ route('admin.accounts.store', array_filter(['tab_session' => request()->query('tab_session')])) }}">
                @csrf
                @if(request()->filled('tab_session'))<input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">@endif
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-950 px-6 py-5 text-white">
                    <div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">Access management</p><h2 class="mt-1 text-xl font-black">Create an admin account</h2><p class="mt-1 text-xs text-white/60">This creates a separate login with full admin-portal access.</p></div>
                    <button type="button" onclick="document.getElementById('create-admin-account').close()" class="flex h-10 w-10 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="grid max-h-[65vh] gap-4 overflow-y-auto p-6 sm:grid-cols-2">
                    <label class="text-sm font-bold text-slate-700">First name<input name="admin_first_name" value="{{ old('admin_first_name') }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Middle name<input name="admin_middle_name" value="{{ old('admin_middle_name') }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Last name<input name="admin_last_name" value="{{ old('admin_last_name') }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">ID number<input name="admin_employee_id" value="{{ old('admin_employee_id') }}" required autocomplete="username" placeholder="e.g. 2026-001" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Position<input name="admin_position" value="{{ old('admin_position') }}" required placeholder="e.g. HR Officer" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Department<input name="admin_department" value="{{ old('admin_department') }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700 sm:col-span-2">Access level<select name="admin_access_level" required onchange="document.getElementById('limited-admin-permissions').classList.toggle('hidden',this.value!=='limited')" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"><option value="full" @selected(old('admin_access_level','limited')==='full')>Full access - all modules</option><option value="limited" @selected(old('admin_access_level','limited')==='limited')>Limited access - selected modules</option></select></label>
                    @php
                        $permissionOptions = [
                            'dashboard' => ['Dashboard', 'fa-house'], 'employees' => ['Employees', 'fa-users'],
                            'leave' => ['Leave Management', 'fa-clipboard'], 'payslip' => ['Payslip', 'fa-file-invoice-dollar'],
                            'communication' => ['Communication', 'fa-comments'], 'reports' => ['Reports', 'fa-chart-line'],
                            'logs' => ['Activity Logs', 'fa-clock-rotate-left'], 'hiring' => ['Hiring', 'fa-briefcase'],
                            'loads' => ['Loads', 'fa-book-open-reader'], 'matrix' => ['Matrix', 'fa-table-cells'],
                            'resignations' => ['Resignations', 'fa-user-minus'], 'calendar' => ['Calendar', 'fa-calendar-days'],
                        ];
                        $oldPermissions = old('admin_permissions', ['dashboard']);
                    @endphp
                    <div id="limited-admin-permissions" class="admin-permission-panel {{ old('admin_access_level','limited') === 'limited' ? '' : 'hidden' }} rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 sm:col-span-2">
                        <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-800">Allowed modules</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                            @foreach($permissionOptions as $key => [$label, $icon])
                                <label class="admin-permission-option flex cursor-pointer items-center gap-2 rounded-xl border border-emerald-100 bg-white px-3 py-2.5 text-xs font-bold text-slate-700 hover:border-emerald-300"><input type="checkbox" name="admin_permissions[]" value="{{ $key }}" class="h-4 w-4 accent-emerald-600" @checked(in_array($key, $oldPermissions, true))><i class="fa-solid {{ $icon }} w-4 text-emerald-600"></i>{{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <label class="text-sm font-bold text-slate-700">Temporary password<input type="password" name="admin_password" required minlength="8" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Confirm password<input type="password" name="admin_password_confirmation" required minlength="8" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    @if($errors->createAdmin->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700 sm:col-span-2">{{ $errors->createAdmin->first() }}</div>@endif
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800 sm:col-span-2"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Only grant this account to trusted staff. It will have full administrator access immediately.</div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4"><button type="button" onclick="document.getElementById('create-admin-account').close()" class="admin-modal-cancel rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancel</button><button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700"><i class="fa-solid fa-user-shield"></i>Create Account</button></div>
            </form>
        </dialog>
        <dialog id="edit-admin-account" class="m-auto w-[calc(100vw-2rem)] max-w-2xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-0 shadow-2xl">
            <form id="edit-admin-account-form" method="POST" action="">
                @csrf
                @if(request()->filled('tab_session'))<input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">@endif
                <input type="hidden" id="edit_admin_id" name="edit_admin_id" value="{{ old('edit_admin_id') }}">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-950 px-6 py-5 text-white">
                    <div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">Access management</p><h2 class="mt-1 text-xl font-black">Edit administrator</h2><p class="mt-1 text-xs text-white/60">Update account details and module access.</p></div>
                    <button type="button" onclick="document.getElementById('edit-admin-account').close()" class="flex h-10 w-10 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="grid max-h-[65vh] gap-4 overflow-y-auto p-6 sm:grid-cols-2">
                    <label class="text-sm font-bold text-slate-700">First name<input id="edit_admin_first_name" name="edit_admin_first_name" value="{{ old('edit_admin_first_name') }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Middle name<input id="edit_admin_middle_name" name="edit_admin_middle_name" value="{{ old('edit_admin_middle_name') }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Last name<input id="edit_admin_last_name" name="edit_admin_last_name" value="{{ old('edit_admin_last_name') }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">ID number<input id="edit_admin_employee_id" name="edit_admin_employee_id" value="{{ old('edit_admin_employee_id') }}" required autocomplete="username" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Position<input id="edit_admin_position" name="edit_admin_position" value="{{ old('edit_admin_position') }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Department<input id="edit_admin_department" name="edit_admin_department" value="{{ old('edit_admin_department') }}" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700 sm:col-span-2">Access level<select id="edit_admin_access_level" name="edit_admin_access_level" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"><option value="full">Full access - all modules</option><option value="limited">Limited access - selected modules</option></select></label>
                    <div id="edit-limited-admin-permissions" class="admin-permission-panel rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 sm:col-span-2">
                        <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-800">Allowed modules</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                            @foreach($permissionOptions as $key => [$label, $icon])
                                <label class="admin-permission-option flex cursor-pointer items-center gap-2 rounded-xl border border-emerald-100 bg-white px-3 py-2.5 text-xs font-bold text-slate-700 hover:border-emerald-300"><input type="checkbox" name="edit_admin_permissions[]" value="{{ $key }}" class="h-4 w-4 accent-emerald-600" @checked(in_array($key, old('edit_admin_permissions', []), true))><i class="fa-solid {{ $icon }} w-4 text-emerald-600"></i>{{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <label class="text-sm font-bold text-slate-700">New password <span class="font-normal text-slate-400">(optional)</span><input type="password" name="edit_admin_password" minlength="8" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    <label class="text-sm font-bold text-slate-700">Confirm new password<input type="password" name="edit_admin_password_confirmation" minlength="8" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"></label>
                    @if($errors->editAdmin->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700 sm:col-span-2">{{ $errors->editAdmin->first() }}</div>@endif
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4"><button type="button" onclick="document.getElementById('edit-admin-account').close()" class="admin-modal-cancel rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancel</button><button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700"><i class="fa-solid fa-floppy-disk"></i>Save Changes</button></div>
            </form>
        </dialog>
        @if($errors->getBag('default')->any())
            <script>document.addEventListener('DOMContentLoaded',()=>document.getElementById('admin-profile-editor')?.showModal());</script>
        @endif
        @if($errors->createAdmin->any())
            <script>document.addEventListener('DOMContentLoaded',()=>document.getElementById('create-admin-account')?.showModal());</script>
        @endif
        <script>
            (() => {
                const modal = document.getElementById('edit-admin-account');
                const form = document.getElementById('edit-admin-account-form');
                const accessLevel = document.getElementById('edit_admin_access_level');
                const permissionPanel = document.getElementById('edit-limited-admin-permissions');
                const updateUrlBase = @json(url('system/my-profile/admin-accounts'));

                function syncPermissionPanel() {
                    permissionPanel?.classList.toggle('hidden', accessLevel?.value !== 'limited');
                }

                function openEditAdmin(account) {
                    if (!modal || !form || !account?.id) return;
                    form.action = `${updateUrlBase}/${account.id}`;
                    document.getElementById('edit_admin_id').value = account.id;
                    ['first_name', 'middle_name', 'last_name', 'employee_id', 'position', 'department'].forEach((field) => {
                        const input = document.getElementById(`edit_admin_${field}`);
                        if (input) input.value = account[field] || '';
                    });
                    accessLevel.value = account.access_level || 'limited';
                    const allowed = new Set(account.permissions || []);
                    form.querySelectorAll('input[name="edit_admin_permissions[]"]').forEach((checkbox) => {
                        checkbox.checked = allowed.has(checkbox.value);
                    });
                    form.querySelectorAll('input[type="password"]').forEach((input) => { input.value = ''; });
                    syncPermissionPanel();
                    modal.showModal();
                }

                document.querySelectorAll('.admin-account-card').forEach((card) => {
                    card.addEventListener('click', () => {
                        try {
                            openEditAdmin(JSON.parse(card.dataset.adminAccount || '{}'));
                        } catch (_) {
                            // Ignore malformed account data.
                        }
                    });
                });
                accessLevel?.addEventListener('change', syncPermissionPanel);

                @if($errors->editAdmin->any())
                    document.addEventListener('DOMContentLoaded', () => {
                        const accountId = @json(old('edit_admin_id'));
                        if (!accountId || !modal || !form) return;
                        form.action = `${updateUrlBase}/${accountId}`;
                        accessLevel.value = @json(old('edit_admin_access_level', 'limited'));
                        syncPermissionPanel();
                        modal.showModal();
                    });
                @endif
            })();
        </script>
    </main>
</div>
</body>
</html>
