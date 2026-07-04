<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documents</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body { font-family: Inter, system-ui, sans-serif; transition: margin-left 0.3s ease; }

        main {
            transition: margin-left 0.3s ease;
        }

        aside:not(:hover) ~ main {
            margin-left: 4rem;
        }

        aside:hover ~ main {
            margin-left: 14rem;
        }

        #employee-document-page .employee-document-reveal {
            opacity: 0;
            transform: translateY(24px);
            transition:
                opacity 0.7s ease,
                transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-document-delay, 0ms);
        }

        #employee-document-page .employee-document-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        #employee-document-page .employee-document-card-motion {
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }

        #employee-document-page .employee-document-card-motion:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        #employee-document-page .employee-document-icon-pop {
            opacity: 0;
            transform: scale(0.86) rotate(-4deg);
            transition:
                opacity 0.55s ease,
                transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-document-delay, 120ms);
        }

        #employee-document-page .is-visible .employee-document-icon-pop,
        #employee-document-page .employee-document-icon-pop.is-visible {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }

        #employee-document-page .employee-document-progress-fill {
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.85s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-document-delay, 180ms);
        }

        #employee-document-page .employee-document-progress-fill.is-visible {
            transform: scaleX(1);
        }

        @media (prefers-reduced-motion: reduce) {
            #employee-document-page .employee-document-reveal,
            #employee-document-page .employee-document-icon-pop,
            #employee-document-page .employee-document-progress-fill,
            #employee-document-page .employee-document-card-motion {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>
<body class="bg-slate-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    @include('components.employeeSideBar')

    <!-- MAIN -->
    <main class="flex-1 ml-16 transition-all duration-300">
<div id="employee-document-page" class="p-4 md:p-8 space-y-8 pt-4">
        @php
            $allDocumentCount = $allDocuments->count();
            $folderCount = $folders->count();
            $missingDocumentCount = count($missingDocuments ?? []);
            $uploadedRequiredCount = max($allDocumentCount - $missingDocumentCount, 0);
            $completionBase = max($allDocumentCount + $missingDocumentCount, 1);
            $completionPercent = min((int) round(($uploadedRequiredCount / $completionBase) * 100), 100);
        @endphp

        <section class="employee-document-reveal relative overflow-hidden rounded-[2rem] border border-emerald-950/40 bg-gradient-to-br from-slate-950 via-emerald-950 to-emerald-800 p-6 text-white shadow-2xl md:p-8" style="--employee-document-delay: 0ms;">
            <div class="absolute -right-8 -top-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="relative grid gap-6 xl:grid-cols-[1.7fr_1fr] xl:items-end">
                <div class="space-y-5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-emerald-100">
                        Document Center
                    </div>
                    <div>
                        <h3 class="text-3xl font-black tracking-tight md:text-5xl">Keep your employee records complete, organized, and easy to retrieve.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-50 md:text-base">
                            Upload required files, organize personal folders, and track missing requirements from one document workspace.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="employee-document-card-motion employee-document-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-document-delay: 80ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">All Documents</p>
                            <p class="mt-2 text-2xl font-black">{{ $allDocumentCount }}</p>
                        </div>
                        <div class="employee-document-card-motion employee-document-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-document-delay: 120ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Folders</p>
                            <p class="mt-2 text-2xl font-black">{{ $folderCount }}</p>
                        </div>
                        <div class="employee-document-card-motion employee-document-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-document-delay: 160ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Missing</p>
                            <p class="mt-2 text-2xl font-black">{{ $missingDocumentCount }}</p>
                        </div>
                        <div class="employee-document-card-motion employee-document-reveal rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm" style="--employee-document-delay: 200ms;">
                            <p class="text-xs uppercase tracking-wide text-emerald-100">Completion</p>
                            <p class="mt-2 text-2xl font-black">{{ $completionPercent }}%</p>
                        </div>
                    </div>
                </div>

                <div class="employee-document-card-motion employee-document-reveal rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur-sm" style="--employee-document-delay: 120ms;">
                    <div class="mb-4 flex justify-end">
                        <div class="relative group">
                            <button class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white backdrop-blur-sm transition hover:bg-white/20">
                                <i class="fa fa-user"></i>
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
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Records Snapshot</p>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-emerald-50">Requirements completed</span>
                                <span class="font-semibold">{{ $completionPercent }}%</span>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/15">
                                <div class="employee-document-progress-fill h-full rounded-full bg-emerald-300" style="width: {{ $completionPercent }}%; --employee-document-delay: 220ms;"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="employee-document-card-motion rounded-2xl bg-white/10 p-4">
                                <p class="text-xs uppercase tracking-wide text-emerald-100">Latest Upload</p>
                                <p class="mt-2 text-sm font-bold text-white">{{ $latestDocument?->type ?: ($latestDocument?->filename ?? 'None yet') }}</p>
                            </div>
                            <div class="employee-document-card-motion rounded-2xl bg-white/10 p-4">
                                <p class="text-xs uppercase tracking-wide text-emerald-100">Viewing</p>
                                <p class="mt-2 text-sm font-bold text-white">{{ $activeFolderLabel }}</p>
                            </div>
                        </div>
                        <p class="text-xs leading-5 text-emerald-50">
                            Keep required files updated to avoid delays in onboarding, payroll processing, and employee record validation.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <article class="employee-document-card-motion employee-document-reveal rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm" style="--employee-document-delay: 120ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-document-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-lg shadow-slate-900/20" style="--employee-document-delay: 180ms;">
                        <i class="fa-solid fa-folder-open text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Workspace</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $allDocumentCount }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Stored Files</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Total uploaded files currently saved across your personal folders and unfiled records.</p>
            </article>

            <article class="employee-document-card-motion employee-document-reveal rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm" style="--employee-document-delay: 160ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-document-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20" style="--employee-document-delay: 220ms;">
                        <i class="fa-solid fa-circle-check text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Ready</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $uploadedRequiredCount }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Completed Requirements</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Documents already uploaded and counted toward your required employee file submission.</p>
            </article>

            <article class="employee-document-card-motion employee-document-reveal rounded-[1.75rem] border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm" style="--employee-document-delay: 200ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-document-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20" style="--employee-document-delay: 260ms;">
                        <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Needs Action</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $missingDocumentCount }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Missing Requirements</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Use the upload panel to submit any missing files listed under your 201 file requirements.</p>
            </article>

            <article class="employee-document-card-motion employee-document-reveal rounded-[1.75rem] border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-6 shadow-sm" style="--employee-document-delay: 240ms;">
                <div class="flex items-start justify-between gap-4">
                    <div class="employee-document-icon-pop flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-500 text-white shadow-lg shadow-sky-500/20" style="--employee-document-delay: 300ms;">
                        <i class="fa-solid fa-folder-tree text-2xl"></i>
                    </div>
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">Organized</span>
                </div>
                <h3 class="mt-8 text-3xl font-black text-slate-900">{{ $folderCount }}</h3>
                <p class="mt-1 text-sm font-medium text-slate-600">Created Folders</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">Custom folders available for grouping personal files, IDs, and supporting employee documents.</p>
            </article>
        </section>

        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">

            <!-- 201 FILE -->
            <div id="document-upload-area" class="employee-document-reveal rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6" style="--employee-document-delay: 280ms;">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Required Upload</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-900">201 File Submission</h2>
                        <p class="text-sm text-gray-500 mt-2">
                            Upload one document at a time for your employee 201 file.
                        </p>
                    </div>
                    <div class="employee-document-icon-pop relative flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700" style="--employee-document-delay: 340ms;">
                        <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                        @if($missingDocumentCount > 0)
                            <span class="absolute -right-2 -top-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white">
                                !
                            </span>
                        @endif
                    </div>
                </div>

                <div class="employee-document-card-motion mb-6 rounded-[1.5rem] border border-emerald-100 bg-gradient-to-r from-emerald-50 to-white p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-emerald-900">Requirement Progress</p>
                            <p class="mt-1 text-xs text-emerald-700">{{ $uploadedRequiredCount }} completed out of {{ $completionBase }} tracked requirements</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700">{{ $completionPercent }}%</span>
                    </div>
                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-emerald-100">
                        <div class="employee-document-progress-fill h-full rounded-full bg-emerald-500" style="width: {{ $completionPercent }}%; --employee-document-delay: 360ms;"></div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <section class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4 md:p-5" aria-labelledby="upload-document-heading">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-sm font-black text-emerald-700">1</span>
                        <div>
                            <h3 id="upload-document-heading" class="font-bold text-slate-900">Upload a required document</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Complete the fields below, then save one file.</p>
                        </div>
                    </div>

                    <form action="{{ route('employee.upload_documents') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label for="document_name" class="mb-1 block text-sm font-medium text-gray-700">Document Name</label>
                            <input
                                id="document_name"
                                type="text"
                                name="document_name"
                                placeholder="{{ !empty($missingDocuments) ? 'e.g. '.$missingDocuments[0] : 'e.g. NBI Clearance' }}"
                                value="{{ old('document_name') }}"
                                required
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            >
                        </div>

                        <div>
                            <label for="folder_key" class="mb-1 block text-sm font-medium text-gray-700">Destination Folder</label>
                            <select
                                id="folder_key"
                                name="folder_key"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">Unfiled</option>
                                @foreach($folders as $folder)
                                    <option value="{{ $folder['key'] }}" @selected(old('folder_key') === $folder['key'])>
                                        {{ $folder['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="employee-document-card-motion rounded-[1.25rem] border-2 border-dashed border-emerald-200 bg-emerald-50/70 p-4">
                            <label for="uploadFile" class="mb-2 block text-sm font-medium text-gray-700">Attach File</label>
                            <input
                                id="uploadFile"
                                type="file"
                                name="uploadFile"
                                required
                                class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-200"
                            >
                            <p class="mt-2 text-xs text-gray-500">PDF, XLSX, DOC, or DOCX up to 5 MB</p>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 sm:w-auto"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Document
                        </button>
                    </form>
                </section>

                <section id="missing-documents-section" class="mt-5 rounded-[1.5rem] border border-slate-200 bg-white p-4 md:p-5" aria-labelledby="requirements-heading">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-sm font-black text-rose-700">2</span>
                        <div>
                            <h3 id="requirements-heading" class="font-bold text-slate-900">Requirements needing attention</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Use this checklist when naming your next upload.</p>
                        </div>
                    </div>
                    @if(!empty($missingDocuments))
                        <div class="rounded-[1.25rem] border border-rose-200 bg-rose-50 p-4">
                            <p class="text-xs font-semibold text-rose-700 mb-2">Missing Document(s)</p>
                            <ul class="list-disc ml-5 text-xs text-rose-700 space-y-1">
                                @foreach($missingDocuments as $missingDoc)
                                    <li>{{ $missingDoc }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="rounded-[1.25rem] border border-green-200 bg-green-50 p-4 text-xs text-green-700">
                            Complete: no missing required documents.
                        </div>
                    @endif

                    @if(!empty($documentNotice))
                        <div class="mt-4 rounded-[1.25rem] border border-amber-200 bg-amber-50 p-4">
                            <p class="mb-2 text-xs font-semibold text-amber-700">Admin Notice</p>
                            <p class="text-sm leading-6 text-amber-900">{{ $documentNotice }}</p>
                        </div>
                    @endif
                </section>

                <section class="mt-5 rounded-[1.5rem] border border-slate-200 bg-white p-4 md:p-5" aria-labelledby="latest-upload-heading">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sm font-black text-sky-700">3</span>
                        <h3 id="latest-upload-heading" class="font-bold text-slate-900">Latest Upload</h3>
                    </div>
                    @if($latestDocument)
                        <div class="employee-document-card-motion rounded-[1.5rem] border border-green-300 bg-green-50 p-4">
                            <div class="flex items-center gap-3">
                                <span class="employee-document-icon-pop is-visible icon bg-green-200 text-green-600 w-10 h-10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-circle-check text-xl"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800 truncate">{{ $latestDocument->type ?: ($latestDocument->filename ?? 'Document') }}</p>
                                    <p class="text-sm text-gray-600 truncate">{{ $latestDocument->filename }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Uploaded - {{ $latestDocument->formatted_size }} - {{ $latestDocument->formatted_created_at }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="employee-document-card-motion rounded-[1.5rem] border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-500">
                            No uploaded document yet.
                        </div>
                    @endif
                </section>
            </div>

            <!-- PERSONAL DOCUMENTS -->
            <div id="document-folder-area" class="employee-document-reveal rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm md:p-6" style="--employee-document-delay: 320ms;">
                <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Personal Workspace</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-900">My Personal Documents</h2>
                        <p class="text-gray-500 text-sm mt-2">
                            Create folders, then upload files into the folder you want.
                        </p>
                    </div>

                    <form action="{{ route('employee.document.folder.store') }}" method="POST" class="employee-document-card-motion flex w-full flex-col gap-2 rounded-[1.5rem] border border-sky-200 bg-sky-50 p-4 lg:w-auto lg:min-w-[20rem]">
                        @csrf
                        <label for="folder_name" class="text-xs font-semibold uppercase tracking-wide text-sky-900">Create Folder</label>
                        <div class="flex gap-2">
                            <input
                                id="folder_name"
                                type="text"
                                name="folder_name"
                                value="{{ old('folder_name') }}"
                                placeholder="e.g. Government IDs"
                                class="w-full rounded-xl border border-sky-200 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                            >
                            <button
                                type="submit"
                                class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700"
                            >
                                <i class="fa-solid fa-folder-plus"></i>
                                Create
                            </button>
                        </div>
                    </form>
                </div>

                <div class="employee-document-card-motion mb-5 rounded-[1.5rem] border border-sky-200 bg-gradient-to-r from-sky-50 to-cyan-50 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="employee-document-icon-pop is-visible flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500 text-white shadow-sm">
                                <i class="fa-solid fa-folder-open"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-sky-950">Document Folder</p>
                                <p class="text-xs text-sky-700">
                                    {{ $allDocuments->count() }} {{ \Illuminate\Support\Str::plural('file', $allDocuments->count()) }} across {{ $folders->count() }} {{ \Illuminate\Support\Str::plural('folder', $folders->count()) }}
                                </p>
                            </div>
                        </div>
                        <div class="rounded-xl bg-white/80 px-3 py-2 text-xs font-medium text-sky-900">
                            Viewing: {{ $activeFolderLabel }}
                        </div>
                    </div>
                </div>

                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Browse Area</p>
                        <h3 class="mt-1 font-bold text-slate-900">Choose a folder</h3>
                    </div>
                    <p class="text-xs text-slate-500">Drag files onto a folder to organize them</p>
                </div>

                <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <a href="{{ route('employee.employeeDocument') }}#document-folder-area"
                        class="employee-document-card-motion rounded-2xl border p-4 transition {{ $selectedFolderKey === 'all' ? 'border-sky-400 bg-sky-50 shadow-sm' : 'border-gray-200 bg-white hover:border-sky-200 hover:bg-sky-50/50' }}">
                        <div class="flex items-center gap-3">
                            <span class="employee-document-icon-pop is-visible flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                                <i class="fa-solid fa-layer-group"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">All Files</p>
                                <p class="text-xs text-gray-500">{{ $allDocuments->count() }} {{ \Illuminate\Support\Str::plural('file', $allDocuments->count()) }}</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('employee.employeeDocument', ['folder' => 'unfiled']) }}#document-folder-area"
                        data-folder-drop-target
                        data-folder-key=""
                        class="employee-document-card-motion rounded-2xl border p-4 transition {{ $selectedFolderKey === 'unfiled' ? 'border-amber-400 bg-amber-50 shadow-sm' : 'border-gray-200 bg-white hover:border-amber-200 hover:bg-amber-50/60' }}">
                        <div class="flex items-center gap-3">
                            <span class="employee-document-icon-pop is-visible flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                <i class="fa-solid fa-inbox"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Unfiled</p>
                                <p class="text-xs text-gray-500">{{ $unfiledCount }} {{ \Illuminate\Support\Str::plural('file', $unfiledCount) }}</p>
                            </div>
                        </div>
                    </a>

                    @foreach($folders as $folder)
                        <div
                            data-folder-drop-target
                            data-folder-key="{{ $folder['key'] }}"
                            class="employee-document-card-motion rounded-2xl border p-4 transition {{ $selectedFolderKey === $folder['key'] ? 'border-sky-400 bg-sky-50 shadow-sm' : 'border-gray-200 bg-white hover:border-sky-200 hover:bg-sky-50/50' }}"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <a href="{{ route('employee.employeeDocument', ['folder' => $folder['key']]) }}#document-folder-area" class="flex min-w-0 flex-1 items-center gap-3 overflow-hidden">
                                    <span class="employee-document-icon-pop is-visible flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                                        <i class="fa-solid fa-folder"></i>
                                    </span>
                                    <div class="min-w-0 flex-1 overflow-hidden">
                                        <p class="truncate text-sm font-semibold leading-tight text-gray-900" title="{{ $folder['name'] }}">{{ $folder['name'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $folder['count'] }} {{ \Illuminate\Support\Str::plural('file', $folder['count']) }}</p>
                                    </div>
                                </a>

                                <form
                                    action="{{ route('employee.document.folder.remove', ['folderKey' => $folder['key']]) }}"
                                    method="POST"
                                    onsubmit="return confirm('Remove this folder and all files inside it?');"
                                    class="shrink-0"
                                >
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-700 transition hover:bg-rose-200"
                                        title="Remove folder"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    @if($folders->isEmpty())
                        <div class="employee-document-card-motion rounded-[1.5rem] border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-500 sm:col-span-2 xl:col-span-3">
                            No folders yet. Use the Create Folder box above to add one.
                        </div>
                    @endif
                </div>

                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">File Area</p>
                        <h3 class="mt-1 font-bold text-slate-900">{{ $activeFolderLabel }}</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ $documents->count() }} {{ \Illuminate\Support\Str::plural('file', $documents->count()) }}
                    </span>
                </div>

                <div class="max-h-[34rem] space-y-3 overflow-y-auto rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-3 pr-2">
                    @forelse($documents as $document)
                        @php
                            $fileType = strtoupper(pathinfo((string) ($document->filename ?? ''), PATHINFO_EXTENSION));
                            if ($fileType === '') {
                                $fileType = strtoupper(str_replace('application/', '', (string) ($document->mime_type ?? 'FILE')));
                            }
                            $relativePath = trim(str_replace('\\', '/', (string) ($document->filepath ?? '')), '/');
                            $folderBadge = '';
                            $currentFolderKey = '';
                            if (preg_match('#^uploads/applicant-documents/\d+/([^/]+)/#', $relativePath, $matches)) {
                                $currentFolderKey = (($matches[1] ?? '') === 'unfiled') ? '' : (string) ($matches[1] ?? '');
                                if (($matches[1] ?? '') !== 'unfiled') {
                                    $matchedFolder = $folders->firstWhere('key', $matches[1]);
                                    $folderBadge = is_array($matchedFolder) ? (string) ($matchedFolder['name'] ?? '') : '';
                                }
                            }
                        @endphp
                        <div
                            draggable="true"
                            data-document-drag
                            data-document-id="{{ $document->id }}"
                            data-current-folder-key="{{ $currentFolderKey }}"
                            class="employee-document-card-motion rounded-[1.5rem] border border-gray-200 bg-gradient-to-r from-white to-slate-50 p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md"
                        >
                            <div class="flex items-center gap-4">
                            <span class="employee-document-icon-pop is-visible doc-icon bg-sky-100 text-sky-600 w-11 h-11 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-file-lines"></i>
                            </span>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-gray-800">{{ $document->type ?: ($document->filename ?? 'Document') }}</p>
                                    @if(!empty($document->is_new))
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">New</span>
                                    @endif
                                    @if(!empty($document->is_previous_application))
                                        <span class="rounded-full border border-slate-300 bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700">Previous Application</span>
                                    @endif
                                    @if($folderBadge !== '' && $selectedFolderKey === 'all')
                                        <span class="rounded-full bg-sky-100 px-2 py-1 text-[11px] font-medium text-sky-700">{{ $folderBadge }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500">{{ $fileType }} - {{ $document->formatted_size }} - {{ $document->formatted_created_at }}</p>
                            </div>
                            <div class="relative flex items-center gap-2 group/menu">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300"
                                    title="More options"
                                >
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>

                                <div class="absolute right-0 top-8 z-10 h-4 w-40"></div>

                                <div class="invisible absolute right-0 top-10 z-20 w-56 translate-y-1 rounded-xl border border-gray-200 bg-white p-2 opacity-0 shadow-lg transition duration-150 group-hover/menu:visible group-hover/menu:translate-y-0 group-hover/menu:opacity-100 group-focus-within/menu:visible group-focus-within/menu:translate-y-0 group-focus-within/menu:opacity-100">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50"
                                        data-move-toggle
                                    >
                                        <span class="flex items-center gap-2">
                                            <i class="fa-solid fa-folder-tree w-4"></i>
                                            Move folder
                                        </span>
                                        <i class="fa-solid fa-chevron-right text-xs"></i>
                                    </button>

                                    <div class="mt-2 hidden rounded-xl border border-sky-100 bg-sky-50 p-2" data-move-panel>
                                        <p class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wide text-sky-700">Choose Folder</p>
                                        <form action="{{ route('employee.document.move', ['id' => $document->id]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="folder_key" value="">
                                            <button
                                                type="submit"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs text-gray-600 hover:bg-white"
                                            >
                                                <i class="fa-solid fa-inbox w-4 text-amber-600"></i>
                                                Unfiled
                                            </button>
                                        </form>
                                        @forelse($folders as $folderOption)
                                            <form action="{{ route('employee.document.move', ['id' => $document->id]) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="folder_key" value="{{ $folderOption['key'] }}">
                                                <button
                                                    type="submit"
                                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs text-gray-600 hover:bg-white"
                                                >
                                                    <i class="fa-solid fa-folder w-4 text-sky-600"></i>
                                                    <span class="truncate">{{ $folderOption['name'] }}</span>
                                                </button>
                                            </form>
                                        @empty
                                            <p class="px-2 py-2 text-xs text-gray-500">No folders created yet.</p>
                                        @endforelse
                                    </div>

                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-50"
                                        onclick="return false;"
                                    >
                                        <i class="fa-solid fa-box-archive w-4"></i>
                                        Archive
                                    </button>

                                    <form action="{{ route('employee.remove_document', ['id' => $document->id]) }}" method="POST" onsubmit="return confirm('Remove this file?');">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50"
                                        >
                                            <i class="fa-solid fa-trash w-4"></i>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            </div>
                        </div>
                    @empty
                        <div class="employee-document-card-motion rounded-[1.5rem] border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-500">
                            No files in {{ strtolower($activeFolderLabel) }} yet.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
    </main>
</div>

<style>
.nav {
    @apply flex px-4 py-3 rounded-lg hover:bg-gray-100 cursor-pointer;
}
.nav.active {
    @apply bg-indigo-500 text-white;
}
.status {
    @apply flex items-center gap-4 border rounded-xl p-4 mb-3;
}
.uploaded {
    @apply bg-green-50 border-green-200;
}
.pending {
    @apply bg-yellow-50 border-yellow-200;
}
.icon {
    @apply w-9 h-9 rounded-full flex items-center justify-center;
}
.doc {
    @apply flex items-center gap-4 border rounded-xl p-4 mb-4;
}
.doc-icon {
    @apply w-12 h-12 rounded-xl flex items-center justify-center;
}
.view {
    @apply text-indigo-600 font-medium text-sm hover:underline;
}
</style>

<form id="dragMoveForm" method="POST" action="{{ url('employee/document/__DOC_ID__/move') }}" class="hidden">
    @csrf
    <input type="hidden" name="folder_key" id="dragMoveFolderKey" value="">
</form>

<script>
    const initEmployeeDocumentAnimation = () => {
        const page = document.getElementById('employee-document-page');
        if (!page) return;

        const animatedItems = page.querySelectorAll('.employee-document-reveal, .employee-document-progress-fill');

        if (!('IntersectionObserver' in window)) {
            animatedItems.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.14,
            rootMargin: '0px 0px -40px 0px',
        });

        animatedItems.forEach((item) => observer.observe(item));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeDocumentAnimation, { once: true });
    } else {
        initEmployeeDocumentAnimation();
    }

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

    document.querySelectorAll('[data-move-toggle]').forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const menu = this.closest('.group\\/menu');
            const panel = menu?.querySelector('[data-move-panel]');

            if (!panel) {
                return;
            }

            panel.classList.toggle('hidden');
        });
    });

    const dragMoveForm = document.getElementById('dragMoveForm');
    const dragMoveFolderKeyInput = document.getElementById('dragMoveFolderKey');
    const moveActionTemplate = dragMoveForm?.getAttribute('action') ?? '';
    let draggedDocumentId = null;
    let draggedCurrentFolderKey = null;

    document.querySelectorAll('[data-document-drag]').forEach((card) => {
        card.addEventListener('dragstart', function (event) {
            draggedDocumentId = this.dataset.documentId ?? null;
            draggedCurrentFolderKey = this.dataset.currentFolderKey ?? '';
            this.classList.add('opacity-60', 'scale-[0.99]');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedDocumentId ?? '');
        });

        card.addEventListener('dragend', function () {
            this.classList.remove('opacity-60', 'scale-[0.99]');
            draggedDocumentId = null;
            draggedCurrentFolderKey = null;
            document.querySelectorAll('[data-folder-drop-target]').forEach((target) => {
                target.classList.remove('ring-2', 'ring-sky-300', 'border-sky-400', 'bg-sky-100');
            });
        });
    });

    document.querySelectorAll('[data-folder-drop-target]').forEach((target) => {
        target.addEventListener('dragover', function (event) {
            if (!draggedDocumentId) {
                return;
            }

            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.classList.add('ring-2', 'ring-sky-300', 'border-sky-400', 'bg-sky-100');
        });

        target.addEventListener('dragleave', function () {
            this.classList.remove('ring-2', 'ring-sky-300', 'border-sky-400', 'bg-sky-100');
        });

        target.addEventListener('drop', function (event) {
            event.preventDefault();
            this.classList.remove('ring-2', 'ring-sky-300', 'border-sky-400', 'bg-sky-100');

            if (!draggedDocumentId || !dragMoveForm || !dragMoveFolderKeyInput || !moveActionTemplate) {
                return;
            }

            const targetFolderKey = this.dataset.folderKey ?? '';
            if ((draggedCurrentFolderKey ?? '') === targetFolderKey) {
                return;
            }

            dragMoveForm.setAttribute('action', moveActionTemplate.replace('__DOC_ID__', draggedDocumentId));
            dragMoveFolderKeyInput.value = targetFolderKey;
            dragMoveForm.submit();
        });
    });

</script>

</body>
</html>



