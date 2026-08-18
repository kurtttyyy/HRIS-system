<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Northeastern College</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        @media (max-width: 767px) {
            aside ~ main,
            aside:not(:hover) ~ main,
            aside:hover ~ main {
                width: 100%;
                min-width: 0;
                margin-left: 0 !important;
            }

            .employee-profile-page {
                padding: .75rem;
                padding-top: .75rem;
                gap: .85rem;
            }
            .employee-profile-hero {
                padding: 1rem;
                border-radius: 1.25rem;
            }
            .employee-profile-identity { gap: .85rem; }
            .employee-profile-avatar,
            .employee-profile-avatar-fallback {
                width: 5.5rem !important;
                height: 5.5rem !important;
                border-radius: 1.15rem !important;
                font-size: 1.65rem !important;
                box-shadow: none !important;
                --tw-ring-offset-shadow: 0 0 #0000 !important;
                --tw-ring-shadow: 0 0 #0000 !important;
            }
            .employee-profile-avatar-status {
                right: -.2rem;
                bottom: -.2rem;
                width: 1.65rem;
                height: 1.65rem;
                border-radius: .6rem;
                font-size: .7rem;
            }
            .employee-profile-name {
                margin-top: .4rem;
                font-size: 1.25rem;
                line-height: 1.25;
            }
            .employee-profile-badges { margin-top: .65rem; gap: .35rem; }
            .employee-profile-badges > span {
                padding: .35rem .6rem;
                font-size: .7rem;
            }
            .employee-profile-photo-panel {
                max-width: none;
                padding: .85rem;
                border-radius: 1rem;
            }
            .employee-profile-photo-panel h3 { font-size: .9rem; }
            .employee-profile-photo-panel h3 + p,
            .employee-profile-photo-panel > div > span { display: none; }
            .employee-profile-photo-form { margin-top: .65rem; }
            .employee-profile-photo-picker {
                display: flex;
                padding: .65rem;
                align-items: center;
                gap: .65rem;
                text-align: left;
                border-width: 1px;
                border-radius: .85rem;
            }
            .employee-profile-photo-picker > span:first-child {
                margin: 0;
                width: 2.25rem;
                height: 2.25rem;
                border-radius: .7rem;
            }
            .employee-profile-photo-picker > span:nth-child(2) { margin-top: 0; }
            .employee-profile-photo-picker > span:last-child { display: none; }
            .employee-profile-photo-form input[type="file"] { font-size: .7rem; }
            .employee-profile-photo-form button {
                width: 100%;
                justify-content: center;
            }
            .employee-profile-stats {
                margin-top: 1rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .55rem;
            }
            .employee-profile-stats > div {
                min-height: 5rem;
                padding: .75rem .45rem;
                display: flex;
                flex-direction: column;
                justify-content: center;
                border-radius: .9rem;
            }
            .employee-profile-stats > div p:first-child { font-size: 1.15rem; }
            .employee-profile-stats > div p:last-child { font-size: .7rem; }
            .employee-profile-details-grid { gap: .85rem; }
            .employee-profile-details-card {
                padding: 1rem;
                border-radius: 1.25rem;
            }
            .employee-profile-details-card > div:first-child { margin-bottom: .85rem; }
            .employee-profile-details-card > div:first-child > div:first-child {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: .8rem;
            }
            .employee-profile-details-card h3 { font-size: 1rem; }
            .employee-profile-field-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .5rem;
            }
            .employee-profile-field-grid > div {
                min-width: 0;
                padding: .75rem;
                border-radius: .8rem;
            }
            .employee-profile-field-grid > div p:first-child {
                font-size: .6rem;
                letter-spacing: .12em;
            }
            .employee-profile-field-grid > div p:last-child {
                margin-top: .3rem;
                font-size: .82rem;
                line-height: 1.35;
                overflow-wrap: anywhere;
            }
        }
    </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#eff6ff,_#f8fafc_42%,_#f8fafc_100%)] text-slate-900">
@php
    $fullName = trim(implode(' ', array_filter([
        $emp->first_name ?? null,
        $emp->middle_name ?? null,
        $emp->last_name ?? null,
    ])));
    $employeeId = $emp->employee->employee_id ?? 'N/A';
    $email = $emp->email ?? 'N/A';
    $phone = $emp->employee->contact_number ?? 'N/A';
    $address = $emp->employee->address ?? 'N/A';
    $position = $emp->employee->position ?? 'Not assigned';
    $department = $emp->employee->department ?? 'Department not available';
    $joinDate = $emp->employee->formatted_employement_date ?? $emp->applicant?->formatted_date_hired ?? '-';
@endphp

<div class="flex min-h-screen">

    @include('components.employeeSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        @include('components.employeeHeader.myProfileHeader')

        <div class="employee-profile-page space-y-8 p-4 pt-20 md:p-8">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="employee-profile-hero relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/80 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl md:p-8">
                <div class="absolute -left-12 top-6 h-28 w-28 rounded-full bg-sky-100/70 blur-3xl"></div>
                <div class="absolute right-10 top-0 h-24 w-24 rounded-full bg-emerald-100/70 blur-3xl"></div>

                <div class="relative">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div class="employee-profile-identity flex items-start gap-5">
                            <div class="relative">
                                @if (!empty($profilePhotoUrl))
                                    <img
                                        src="{{ $profilePhotoUrl }}"
                                        alt="{{ $fullName !== '' ? $fullName : 'Employee profile photo' }}"
                                        class="employee-profile-avatar h-[27rem] w-[27rem] rounded-[2.5rem] object-cover shadow-lg ring-4 ring-emerald-300"
                                    >
                                @else
                                    <div class="employee-profile-avatar-fallback flex h-[27rem] w-[27rem] items-center justify-center rounded-[2.5rem] bg-gradient-to-br from-sky-500 via-indigo-500 to-violet-500 text-8xl font-black text-white shadow-lg ring-4 ring-emerald-300">
                                        {{ $emp->initials }}
                                    </div>
                                @endif
                                <div class="employee-profile-avatar-status absolute -bottom-2 -right-2 flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>

                            <div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">
                                    Employee Profile
                                </div>
                                <h2 class="employee-profile-name mt-3 text-3xl font-black tracking-tight text-slate-900 md:text-4xl">
                                    {{ $fullName !== '' ? $fullName : 'Employee Name' }}
                                </h2>
                                <p class="mt-2 text-base font-medium text-slate-600">{{ $position }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $department }}</p>

                                <div class="employee-profile-badges mt-4 flex flex-wrap gap-3">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                        {{ $employmentStatus ?? 'Active' }}
                                    </span>
                                    @if(!empty($rehireMeta['is_rehire']))
                                        <span class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-700">
                                            <i class="fa fa-rotate-left"></i>
                                            Rehired
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                                        <i class="fa fa-id-badge"></i>
                                        {{ $employeeId }}
                                    </span>
                                </div>
                                @if(!empty($rehireMeta['label']))
                                    <p class="mt-3 text-sm font-medium text-violet-700">{{ $rehireMeta['label'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="employee-profile-photo-panel w-full max-w-md rounded-[1.75rem] border border-slate-200 bg-white/90 p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Profile Photo</p>
                                    <h3 class="mt-1 text-lg font-black text-slate-900">Update display picture only</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">
                                        Personal and employment details stay read-only here. You can only change your profile picture from this page.
                                    </p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Photo only</span>
                            </div>

                            <form action="{{ route('employee.upload_documents') }}" method="POST" enctype="multipart/form-data" class="employee-profile-photo-form mt-4 space-y-3">
                                @csrf
                                <input type="hidden" name="document_name" value="PROFILE_PHOTO">
                                <input type="hidden" name="folder_key" value="">

                                <label for="profile-photo-upload" class="employee-profile-photo-picker block rounded-[1.25rem] border-2 border-dashed border-sky-200 bg-sky-50/70 px-4 py-4 text-center transition hover:border-sky-300 hover:bg-sky-50">
                                    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-sky-600 shadow-sm">
                                        <i class="fa fa-camera text-lg"></i>
                                    </span>
                                    <span class="mt-2 block text-sm font-semibold text-slate-700">Choose a new profile photo</span>
                                    <span class="mt-1 block text-xs text-slate-500">Accepted: JPG, JPEG, PNG, GIF, WEBP</span>
                                </label>
                                <input
                                    id="profile-photo-upload"
                                    type="file"
                                    name="uploadFile"
                                    accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
                                    required
                                    class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                                >

                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    <i class="fa fa-upload"></i>
                                    Save Profile Picture
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="employee-profile-stats mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4 text-center">
                            <p class="text-2xl font-black text-slate-900">{{ $serviceDurationText ?? '0Y 0M 0D' }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-500">Length of Service</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4 text-center">
                            <p class="text-2xl font-black text-slate-900">{{ number_format((float) ($attendanceRatePercent ?? 0), 1) }}%</p>
                            <p class="mt-1 text-sm font-medium text-slate-500">Attendance Rate</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4 text-center">
                            <p class="text-2xl font-black text-slate-900">{{ rtrim(rtrim(number_format((float) ($leaveDaysUsed ?? 0), 1, '.', ''), '0'), '.') }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-500">Leave Days Used</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4 text-center">
                            <p class="text-2xl font-black {{ $employmentStatusClass ?? 'text-emerald-600' }}">{{ $employmentStatus ?? 'Active' }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-500">Current Status</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="employee-profile-details-grid grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="employee-profile-details-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                            <i class="fa fa-user"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Profile Details</p>
                            <h3 class="mt-1 text-xl font-black text-slate-900">Personal Information</h3>
                        </div>
                    </div>

                    <div class="employee-profile-field-grid grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Employee ID</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $employeeId }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Email</p>
                            <p class="mt-2 break-words text-lg font-bold text-slate-900">{{ $email }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Phone</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $phone !== '' ? $phone : 'N/A' }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4 md:col-span-2">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Address</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $address !== '' ? $address : 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="employee-profile-details-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <i class="fa fa-briefcase"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Work Profile</p>
                            <h3 class="mt-1 text-xl font-black text-slate-900">Employment Details</h3>
                        </div>
                    </div>

                    <div class="employee-profile-field-grid grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Position</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $position }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Department</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $department }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Join Date</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $joinDate }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Age</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $ageDisplay ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4 md:col-span-2">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Reporting Manager</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">Michael Chen</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
    const sidebar = document.querySelector('aside');
    const main = document.querySelector('main');

    if (sidebar && main) {
        sidebar.addEventListener('mouseenter', function() {
            main.classList.remove('ml-16');
            main.classList.add('ml-56');
        });

        sidebar.addEventListener('mouseleave', function() {
            main.classList.remove('ml-56');
            main.classList.add('ml-16');
        });
    }
</script>

</body>
</html>
