@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .apply-shell {
        max-width: 1240px;
    }

    .apply-card {
        border: 1px solid rgba(22, 101, 52, 0.14);
        border-radius: 1.2rem;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.1);
        overflow: hidden;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdfb 100%);
    }

    .apply-card-body {
        padding: 2rem;
    }

    .apply-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .apply-hero h2 {
        font-size: clamp(1.6rem, 2.2vw, 2.2rem);
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .apply-hero-subtext {
        color: #5b6472 !important;
        margin-bottom: 0;
    }

    .apply-meta {
        border: 1px solid rgba(34, 197, 94, 0.2);
        border-radius: 0.9rem;
        background: linear-gradient(145deg, rgba(22, 163, 74, 0.08), rgba(234, 179, 8, 0.08));
        padding: 0.7rem 0.95rem;
        min-width: 220px;
    }

    .apply-meta-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #1e7a3f;
        margin-bottom: 0.25rem;
    }

    .apply-meta-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #123f26;
        margin: 0;
    }

    .step-progress-shell {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
        margin: 1rem 0 1.4rem;
    }

    .step-progress-bar {
        height: 100%;
        width: 20%;
        border-radius: inherit;
        background: linear-gradient(90deg, #16a34a, #22c55e);
        transition: width 0.35s ease;
    }

    .stepper1 {
        margin-bottom: 1rem;
    }

    .circle1 {
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .step1.active .circle1,
    .step1.completed1 .circle1 {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(22, 163, 74, 0.2);
    }

    .form-step {
        animation: fadeUpIn 0.35s ease both;
    }

    @keyframes fadeUpIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .floating-input input,
    .floating-input select {
        border-radius: 0.65rem;
        border: 1px solid #d5dbe3;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .floating-input input:focus,
    .floating-input select:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
        transform: translateY(-1px);
    }

    #experienceForm .floating-input input:focus,
    #experienceForm .floating-input select:focus,
    #experienceForm .floating-input input:not(:placeholder-shown),
    #experienceForm .floating-input select:not([value=""]) {
        border-color: #16a34a;
        box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.2), 0 10px 22px rgba(22, 163, 74, 0.12);
        background: linear-gradient(180deg, #ffffff 0%, #f4fff8 100%);
    }

    #experienceForm .floating-input label {
        transition: color 0.2s ease, letter-spacing 0.2s ease;
    }

    #experienceForm .floating-input input:focus + label,
    #experienceForm .floating-input input:not(:placeholder-shown) + label,
    #experienceForm .floating-input select:focus + label,
    #experienceForm .floating-input select:not([value=""]) + label {
        color: #0f7a39;
        letter-spacing: 0.01em;
        font-weight: 700;
    }

    .application-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin: 1.25rem 0 0.9rem;
    }

    .application-section-heading h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: #111827;
    }

    .application-section-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid rgba(22, 163, 74, 0.22);
        border-radius: 999px;
        background: #f0fdf4;
        color: #047857;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        padding: 0.35rem 0.65rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .education-card,
    .degree-card,
    .work-experience-card {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
    }

    .education-card,
    .degree-card {
        padding: 1rem;
    }

    .education-card-title,
    .degree-card-title {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.9rem;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        padding: 0.35rem 0.65rem;
        text-transform: uppercase;
    }

    .degree-card {
        position: relative;
        overflow: hidden;
        margin-bottom: 0.9rem;
    }

    .degree-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, #16a34a, #0f766e);
    }

    .work-experience-card {
        padding: 1.05rem;
    }

    .work-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .work-field-grid .floating-input {
        margin-bottom: 0 !important;
    }

    .fresh-graduate-card {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        border: 1px solid rgba(22, 163, 74, 0.18);
        border-radius: 0.85rem;
        background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
        padding: 0.8rem 0.9rem;
    }

    .fresh-graduate-card .form-check-input {
        margin-top: 0;
    }

    .add-entry-btn {
        border-color: rgba(22, 163, 74, 0.38) !important;
        border-radius: 999px !important;
        background: #f0fdf4 !important;
        color: #047857 !important;
        font-weight: 800 !important;
        box-shadow: 0 10px 20px rgba(22, 163, 74, 0.1);
    }

    .add-entry-btn:hover {
        background: #16a34a !important;
        color: #fff !important;
        transform: translateY(-1px);
    }

    .remove-degree-btn {
        border-radius: 999px !important;
        font-weight: 700 !important;
    }

    .skills-control-panel {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
        padding: 1rem;
    }

    .skills-toolbar-title {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0 0 0.9rem;
        color: #047857;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .skills-control-panel .floating-input {
        margin-bottom: 0 !important;
    }

    .skill-add-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: stretch;
    }

    .add-skill-btn {
        min-height: 3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        border: 1px solid #16a34a;
        border-radius: 0.75rem;
        background: #16a34a;
        color: #fff;
        font-weight: 800;
        padding: 0.55rem 1.05rem;
        white-space: nowrap;
        box-shadow: 0 10px 20px rgba(22, 163, 74, 0.18);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .add-skill-btn:hover {
        color: #fff;
        background: #047857;
        border-color: #047857;
        box-shadow: 0 14px 26px rgba(4, 120, 87, 0.22);
        transform: translateY(-1px);
    }

    .add-skill-btn:focus-visible {
        outline: 3px solid rgba(22, 163, 74, 0.28);
        outline-offset: 2px;
    }

    .add-skill-btn i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        font-size: 0.86rem;
    }

    .skill-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.8rem;
    }

    .skill-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid rgba(22, 163, 74, 0.24);
        border-radius: 999px;
        background: #f0fdf4;
        color: #047857;
        font-size: 0.83rem;
        font-weight: 700;
        padding: 0.4rem 0.55rem 0.4rem 0.75rem;
    }

    .skill-chip button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.45rem;
        height: 1.45rem;
        border: none;
        border-radius: 999px;
        background: rgba(4, 120, 87, 0.1);
        color: #047857;
        line-height: 1;
    }

    .review-section-card {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
        padding: 1.1rem;
    }

    .review-section-title {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0 0 1rem;
        color: #047857;
        font-size: 0.92rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .review-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .review-summary-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.85rem;
        background: #fff;
        padding: 0.9rem;
    }

    .review-summary-panel-wide {
        grid-column: 1 / -1;
    }

    .review-panel-title {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0 0 0.7rem;
        color: #111827;
        font-size: 0.85rem;
        font-weight: 800;
    }

    .review-item-list {
        display: grid;
        gap: 0.55rem;
    }

    .review-item {
        display: grid;
        grid-template-columns: minmax(7.5rem, 0.35fr) minmax(0, 1fr);
        gap: 0.75rem;
        align-items: start;
        border-top: 1px solid rgba(15, 23, 42, 0.06);
        padding-top: 0.55rem;
    }

    .review-item:first-child {
        border-top: 0;
        padding-top: 0;
    }

    .review-label {
        color: #64748b;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .review-value {
        color: #1f2937;
        font-size: 0.92rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .review-file-list {
        margin: 0;
        padding-left: 1.05rem;
        display: grid;
        gap: 0.22rem;
    }

    .review-file-list li {
        padding-left: 0.15rem;
    }

    .review-file-list li::marker {
        color: #059669;
    }

    .review-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .review-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgba(22, 163, 74, 0.25);
        border-radius: 999px;
        background: #f0fdf4;
        color: #047857;
        font-size: 0.8rem;
        font-weight: 800;
        padding: 0.35rem 0.65rem;
    }

    @media (max-width: 575.98px) {
        .application-section-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .work-field-grid {
            grid-template-columns: 1fr;
        }

        .skill-add-row {
            grid-template-columns: 1fr;
        }

        .add-skill-btn {
            width: 100%;
        }

        .review-summary-grid,
        .review-item {
            grid-template-columns: 1fr;
        }
    }

    .upload-area {
        position: relative;
        border-radius: 0.9rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .upload-area:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.1);
    }

    .upload-area.is-selected {
        background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: rgba(22, 163, 74, 0.45) !important;
    }

    .upload-clear-btn {
        position: absolute;
        top: 0.85rem;
        right: 0.85rem;
        display: none;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        height: 2rem;
        border: none;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.08);
        color: #475569;
        font-size: 1rem;
        line-height: 1;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        z-index: 2;
    }

    .upload-clear-btn:hover {
        background: rgba(220, 38, 38, 0.12);
        color: #b91c1c;
        transform: scale(1.05);
    }

    .upload-area.is-selected .upload-clear-btn {
        display: inline-flex;
    }

    .application-intake-grid {
        margin-top: 1rem;
    }

    .application-intake-card {
        border: 1px solid rgba(22, 101, 52, 0.13);
        border-radius: 1.1rem;
        background: #ffffff;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.07);
    }

    .application-scan-card {
        padding: 1.15rem;
        background:
            radial-gradient(circle at top right, rgba(34, 197, 94, 0.12), transparent 32%),
            linear-gradient(180deg, #ffffff 0%, #fbfefc 100%);
    }

    .intake-upload-zone {
        position: relative;
        display: grid;
        place-items: center;
        min-height: 13rem;
        border: 2px dashed rgba(22, 163, 74, 0.32);
        border-radius: 1rem;
        background: rgba(240, 253, 244, 0.52);
        text-align: center;
        cursor: pointer;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .intake-upload-zone:hover,
    .intake-upload-zone.is-ready {
        transform: translateY(-2px);
        border-color: rgba(22, 163, 74, 0.58);
        background: #f0fdf4;
        box-shadow: 0 16px 30px rgba(22, 101, 52, 0.12);
    }

    .intake-upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .intake-upload-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        margin-bottom: 0.8rem;
        border-radius: 1.3rem;
        background: #dcfce7;
        color: #15803d;
        font-size: 2rem;
    }

    .scan-progress-track {
        height: 0.55rem;
        overflow: hidden;
        border-radius: 999px;
        background: #e5e7eb;
    }

    .scan-progress-bar {
        width: 0%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #16a34a, #22c55e);
        transition: width 0.35s ease;
    }

    .scan-status-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.9rem;
        background: #ffffff;
        padding: 0.85rem;
    }

    .scan-status-list {
        display: grid;
        gap: 0.55rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .scan-status-list li {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .scan-status-list li.is-complete {
        color: #166534;
    }

    .step-actions {
        position: sticky;
        bottom: 0;
        z-index: 3;
        padding-top: 0.75rem;
        margin-top: 1.25rem !important;
        background: linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,0.96) 36%, #ffffff 100%);
    }

    .step-actions.d-flex {
        gap: 0.75rem;
    }

    .personal-info-actions {
        width: 100%;
        align-self: stretch;
    }

    .step-actions .btn {
        min-width: 120px;
        border-radius: 0.65rem;
        font-weight: 600;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .step-actions .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    }

    @media (max-width: 992px) {
        .apply-card-body {
            padding: 1.2rem;
        }

        .apply-hero {
            flex-direction: column;
            gap: 0.75rem;
        }

        .apply-meta {
            width: 100%;
        }
    }

    .field-error-highlight {
        border: 1px solid rgba(220, 38, 38, 0.55) !important;
        background: rgba(220, 38, 38, 0.08) !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.16);
        border-radius: 0.5rem;
        animation: errorPulse 0.55s ease-in-out 2, errorShake 0.35s ease-in-out 1;
    }

    @keyframes errorPulse {
        0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.0); }
        50% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0.18); }
        100% { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.16); }
    }

    @keyframes errorShake {
        0%, 100% { transform: translateX(0); }
        20% { transform: translateX(-4px); }
        40% { transform: translateX(4px); }
        60% { transform: translateX(-3px); }
        80% { transform: translateX(3px); }
    }

    .year-field-transition {
        overflow: visible;
        max-height: 160px;
        opacity: 1;
        transform: translateY(0);
        transition: max-height 0.55s ease, opacity 0.45s ease, transform 0.45s ease, margin 0.45s ease;
    }

    .year-field-transition.year-hidden {
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transform: translateY(-6px);
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        pointer-events: none;
    }
</style>


@include('layouts.header')  {{-- UNIVERSAL HEADER --}}


<div class="header-divider"></div>

<main class="container my-5 apply-shell">

    @if (session('popup_error'))
        <div class="alert alert-danger" role="alert">
            {{ session('popup_error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">Please fix the following:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- step 1 --}}
    <div class="card shadow-sm mb-4 animated-card delay-5 hover-card apply-card">
        <div class="card-body apply-card-body">
            <div class="apply-hero">
                <div>
                    <h2 class="mb-1">Apply for {{ $openPosition->title}}</h2>
                    <h6 class="apply-hero-subtext">Please fill out all fields to complete your application</h6>
                </div>
                <div class="apply-meta">
                    <p class="apply-meta-label">Application Flow</p>
                    <p id="currentStepText" class="apply-meta-value">Step 1 of 6: Application</p>
                </div>
            </div>

            <div class="stepper1">

                <div class="step1 completed1">
                    <div class="circle1">1</div>
                    <div class="label1">Application</div>
                </div>

                <div class="line1 completed1"></div>

                <div class="step1 completed1">
                    <div class="circle1">2</div>
                    <div class="label1">Personal Info</div>
                </div>

                <div class="line1 completed1"></div>

                <div class="step1 completed1">
                    <div class="circle1">3</div>
                    <div class="label1">Experience</div>
                </div>

                <div class="line1 completed1"></div>

                <div class="step1">
                    <div class="circle1">4</div>
                    <div class="label1">Documents</div>
                </div>

                <div class="line1"></div>

                <div class="step1">
                    <div class="circle1">5</div>
                    <div class="label1">Review</div>
                </div>

                <div class="line1"></div>

                <div class="step1">
                    <div class="circle1">6</div>
                    <div class="label1">Submit</div>
                </div>

            </div>
<!-- Personal Info Form -->
<form id="formPersonal" action="{{ route('applicant.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="text" name="position" class="form-control" value="{{ $openPosition->id}}" hidden>
    <input type="hidden" id="pds_record_id" name="pds_record_id">
<div id="applicationFormStep" class="mt-4 form-step">
    <h4 class="fw-bold mb-3">Application Details</h4>

    <div class="application-intake-grid">
        <div class="application-intake-card application-scan-card">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Upload Personal Data Sheet</h5>
                    <p class="text-secondary mb-0">Attach your Personal Data Sheet, then scan the uploaded file before continuing.</p>
                </div>
                <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">PDS Only</span>
            </div>

            <label id="intakeUploadZone" class="intake-upload-zone">
                <input type="file" id="intakeUploadInput" accept=".xls,.xlsx,.csv">
                <span class="intake-upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                <strong id="intakeUploadTitle" class="d-block">Upload Personal Data Sheet</strong>
                <span id="intakeUploadSubtitle" class="d-block text-secondary mt-1">Excel files only: XLS, XLSX, CSV</span>
            </label>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button" id="scanUploadedFileButton" class="btn btn-success">
                    <i class="bi bi-search me-1"></i>
                    Scan Personal Data Sheet
                </button>
                <button type="button" id="clearIntakeUploadButton" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    Clear
                </button>
            </div>

            <div class="scan-status-card mt-3">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <span id="scanStateLabel" class="fw-bold text-secondary">Waiting for upload</span>
                    <span id="autoSaveLabel" class="badge rounded-pill bg-light text-secondary">Not saved</span>
                </div>
                <div class="scan-progress-track">
                    <div id="scanProgressBar" class="scan-progress-bar"></div>
                </div>
                <ul class="scan-status-list mt-3">
                    <li id="scanCheckFile"><i class="bi bi-circle"></i> File selected</li>
                    <li id="scanCheckScan"><i class="bi bi-circle"></i> Scan completed</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-auto step-actions">
        <div></div>
        <button type="button" id="btnToPersonal" class="btn btn-primary" disabled>Continue</button>
    </div>
</div>

<div id="personalForm" class="mt-4 d-none form-step">
    <h4 class="fw-bold mb-3">Personal Information</h4>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="floating-input">
                <input type="text" id="first_name" name="first_name" class="form-control" placeholder=" " required>
                <label for="first_name">First Name<span class="required-asterisk"> *</span></label>
            </div>
        </div>

        <div class="col-md-3">
            <div class="floating-input">
                <input type="text" id="middle_name" name="middle_name" class="form-control" placeholder=" ">
                <label for="middle_name">Middle Name</label>
            </div>
        </div>

        <div class="col-md-3">
            <div class="floating-input">
                <input type="text" id="last_name" name="last_name" class="form-control" placeholder=" " required>
                <label for="last_name">Surname<span class="required-asterisk"> *</span></label>
            </div>
        </div>

        <div class="col-md-3">
            <div class="floating-input">
                <input type="text" id="name_extension" name="name_extension" class="form-control" placeholder=" ">
                <label for="name_extension">Name Extension</label>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="floating-input">
                <input type="email" id="email" name="email" class="form-control" placeholder=" " required>
                <label for="email">Email Address<span class="required-asterisk"> *</span></label>
            </div>
        </div>

        <div class="col-md-6">
            <div class="floating-input">
                <input type="text" id="phone" name="phone" class="form-control" placeholder=" " required>
                <label for="phone">Phone Number<span class="required-asterisk"> *</span></label>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="floating-input">
                <select id="sex" name="sex" class="form-select text-secondary" required>
                    <option value="" selected>Select Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <label for="sex">Sex<span class="required-asterisk"> *</span></label>
            </div>
        </div>

        <div class="col-md-4">
            <div class="floating-input">
                <select id="civil_status" name="civil_status" class="form-select text-secondary" required>
                    <option value="" selected>Select Civil Status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Separated">Separated</option>
                    <option value="Other/s">Other/s</option>
                </select>
                <label for="civil_status">Civil Status<span class="required-asterisk"> *</span></label>
            </div>
        </div>

        <div class="col-md-4">
            <div class="floating-input">
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" placeholder=" " required>
                <label for="date_of_birth">Date of Birth<span class="required-asterisk"> *</span></label>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <div class="floating-input">
            <input type="text" id="address" name="address" class="form-control" placeholder=" " required>
            <label for="address">Complete Address<span class="required-asterisk"> *</span></label>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-auto step-actions personal-info-actions">
        <button type="button" id="btnBackToApplication" class="btn btn-secondary">Previous</button>
        <button type="button" id="btnToExperience" class="btn btn-primary">Proceed</button>
    </div>
</div>



    <!-- Work Experience & Education Form -->
<div id="experienceForm" class="mt-4 d-none form-step">

    <div class="application-section-heading">
        <div>
            <span class="application-section-kicker"><i class="bi bi-mortarboard"></i> Education</span>
            <h4 class="mt-2">Educational Background</h4>
        </div>
    </div>

    @php
        $basicEducationRows = [
            'elementary' => 'Elementary',
            'secondary' => 'Secondary',
            'vocational_trade' => 'Vocational / Trade Course',
            'college' => 'College',
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($basicEducationRows as $educationKey => $educationLabel)
            <div class="col-12">
                <div class="education-card">
                    <h6 class="education-card-title"><i class="bi bi-building"></i>{{ $educationLabel }}</h6>
                    <input type="hidden" name="education_levels[{{ $educationKey }}][level]" value="{{ $educationLabel }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="floating-input">
                                <input type="text" id="{{ $educationKey }}_school_name" name="education_levels[{{ $educationKey }}][school_name]" class="form-control education-school-input" placeholder=" ">
                                <label for="{{ $educationKey }}_school_name">School Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="floating-input">
                                <select id="{{ $educationKey }}_year_graduated" name="education_levels[{{ $educationKey }}][year_graduated]" class="form-select text-secondary education-year-select">
                                    <option value="" selected>Select Year Graduated</option>
                                    @for ($year = 2026; $year >= 1900; $year--)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endfor
                                </select>
                                <label for="{{ $educationKey }}_year_graduated">Year Graduated</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div id="bachelor-degrees-container">
    <div class="bachelor-degree-entry degree-card" data-degree-index="0">
    <h6 class="degree-card-title"><i class="bi bi-award"></i>Bachelor Degree</h6>
    <div class="mb-3 floating-input">
        <input type="text" class="form-select text-secondary bachelor-degree-select" id="bachelor_degree_0" name="bachelor_degrees[0][degree]" list="bachelorDegreeOptions" placeholder=" " required>
        <label for="bachelor_degree_0">Bachelor Degree<span class="required-asterisk"> *</span></label>
    </div>

    <div class="mb-3 floating-input year-field-transition year-hidden bachelor-school-wrapper">
        <input type="text" class="form-select bachelor-school-input" id="bachelor_school_name_0" name="bachelor_degrees[0][school_name]" placeholder=" ">
        <label for="bachelor_school_name_0">Bachelor School Name</label>
    </div>

    <div class="mb-3 floating-input year-field-transition year-hidden bachelor-year-wrapper">
        <select class="form-select text-secondary bachelor-year-select" id="bachelor_year_finished_0" name="bachelor_degrees[0][year_finished]">
            <option value="" selected style="color: #6c757d;">Select Year Finished</option>
            @for ($year = 2026; $year >= 1900; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
        <label for="bachelor_year_finished_0">Bachelor Year Finished</label>
    </div>

    <div class="text-end">
        <button type="button" class="btn btn-outline-danger btn-sm remove-degree-btn remove-bachelor-degree d-none">Remove</button>
    </div>
    </div>
    </div>

    <div class="mb-3">
        <button type="button" id="addBachelorDegreeBtn" class="btn btn-outline-success btn-sm add-entry-btn"><i class="bi bi-plus-circle me-1"></i>Add Another Bachelor Degree</button>
    </div>

    <datalist id="bachelorDegreeOptions">
        <option value="Bachelor of Architecture">
        <option value="Bachelor of Science in Accountancy">
        <option value="Bachelor of Science in Nursing">
        <option value="Bachelor of Medicine and Bachelor of Surgery">
        <option value="Bachelor of Science in Medical Technology">
        <option value="Bachelor of Science in Radiologic Technology">
        <option value="Bachelor of Science in Respiratory Therapy">
        <option value="Bachelor of Science in Occupational Therapy">
        <option value="Bachelor of Science in Pharmacy">
        <option value="Bachelor of Science in Dentistry">
        <option value="Bachelor of Science in Psychology">
        <option value="Bachelor of Science in Public Health">
        <option value="Bachelor of Science in Physical Therapy">
        <option value="Bachelor of Science in Computer Science">
        <option value="Bachelor of Science in Information Technology">
        <option value="Bachelor of Science in Information Systems">
        <option value="Bachelor of Science in Cybersecurity">
        <option value="Bachelor of Science in Software Engineering">
        <option value="Bachelor of Science in Data Science">
        <option value="Bachelor of Science in Computer Engineering">
        <option value="Bachelor of Science in Civil Engineering">
        <option value="Bachelor of Science in Mechanical Engineering">
        <option value="Bachelor of Science in Electrical Engineering">
        <option value="Bachelor of Science in Chemical Engineering">
        <option value="Bachelor of Science in Industrial Engineering">
        <option value="Bachelor of Science in Electronics Engineering">
        <option value="Bachelor of Science in Environmental Engineering">
        <option value="Bachelor of Science in Architectural Engineering">
        <option value="Bachelor of Science in Accounting">
        <option value="Bachelor of Science in Economics">
        <option value="Bachelor of Science in Marketing">
        <option value="Bachelor of Science in Finance">
        <option value="Bachelor of Science in Business Administration">
        <option value="Bachelor of Science in Entrepreneurship">
        <option value="Bachelor of Science in Office Administration">
        <option value="Bachelor of Science in Human Resource Management">
        <option value="Bachelor of Science in Operations Management">
        <option value="Bachelor of Science in Tourism Management">
        <option value="Bachelor of Science in Hospitality Management">
        <option value="Bachelor of Science in Hotel and Restaurant Management">
        <option value="Bachelor of Science in Supply Chain Management">
        <option value="Bachelor of Laws">
        <option value="Bachelor of Arts in Political Science">
        <option value="Bachelor of Public Administration">
        <option value="Bachelor of Science in Criminology">
        <option value="Bachelor of Science in Social Work">
        <option value="Bachelor of Arts in Sociology">
        <option value="Bachelor of Arts in Communication">
        <option value="Bachelor of Arts in Journalism">
        <option value="Bachelor of Arts in English">
        <option value="Bachelor of Arts in Filipino">
        <option value="Bachelor of Arts in Philosophy">
        <option value="Bachelor of Elementary Education">
        <option value="Bachelor of Secondary Education">
        <option value="Bachelor of Special Needs Education">
        <option value="Bachelor of Early Childhood Education">
        <option value="Bachelor of Technical-Vocational Teacher Education">
        <option value="Bachelor of Library and Information Science">
        <option value="Bachelor of Fine Arts">
        <option value="Bachelor of Music">
        <option value="Bachelor of Arts in Literature">
        <option value="Bachelor of Arts in History">
        <option value="Bachelor of Science in Graphic Design">
        <option value="Bachelor of Multimedia Arts">
        <option value="Bachelor of Science in Biology">
        <option value="Bachelor of Science in Biochemistry">
        <option value="Bachelor of Science in Chemistry">
        <option value="Bachelor of Science in Physics">
        <option value="Bachelor of Science in Mathematics">
        <option value="Bachelor of Science in Statistics">
        <option value="Bachelor of Science in Agriculture">
        <option value="Bachelor of Science in Forestry">
        <option value="Bachelor of Science in Fisheries">
    </datalist>

    <div id="master-degrees-container">
    <div class="master-degree-entry degree-card" data-degree-index="0">
    <h6 class="degree-card-title"><i class="bi bi-award"></i>Master Degree</h6>
    <div class="mb-3 floating-input">
        <input type="text" class="form-select text-secondary master-degree-select" id="master_degree_0" name="master_degrees[0][degree]" list="masterDegreeOptions" placeholder=" ">
        <label for="master_degree_0">Master Degree</label>
    </div>

    <div class="mb-3 floating-input year-field-transition year-hidden master-school-wrapper">
        <input type="text" class="form-select master-school-input" id="master_school_name_0" name="master_degrees[0][school_name]" placeholder=" ">
        <label for="master_school_name_0">Master School Name</label>
    </div>

    <div class="mb-3 floating-input year-field-transition year-hidden master-year-wrapper">
        <select class="form-select text-secondary master-year-select" id="master_year_finished_0" name="master_degrees[0][year_finished]">
            <option value="" selected style="color: #6c757d;">Select Year Finished</option>
            @for ($year = 2026; $year >= 1900; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
        <label for="master_year_finished_0">Master Year Finished</label>
    </div>

    <div class="text-end">
        <button type="button" class="btn btn-outline-danger btn-sm remove-degree-btn remove-master-degree d-none">Remove</button>
    </div>
    </div>
    </div>

    <div class="mb-3">
        <button type="button" id="addMasterDegreeBtn" class="btn btn-outline-success btn-sm add-entry-btn"><i class="bi bi-plus-circle me-1"></i>Add Another Master Degree</button>
    </div>

    <datalist id="masterDegreeOptions">
        <option value="Master of Arts (MA)">
        <option value="Master of Science (MS/MSc)">
        <option value="Master of Science in Nursing (MSN)">
        <option value="Master of Business Administration (MBA)">
        <option value="Master of Education (MEd)">
        <option value="Master of Fine Arts (MFA)">
        <option value="Master of Laws (LLM)">
        <option value="Master of Social Work (MSW)">
        <option value="Master of Public Health (MPH)">
        <option value="Master of Engineering (MEng)">
        <option value="Master of Research (MRes)">
        <option value="Master of Philosophy (MPhil)">
        <option value="Master of Studies (MSt)">
        <option value="Master of Technology (MTech)">
        <option value="Master of Science in Information Technology (MSIT)">
        <option value="Master of Computer Applications (MCA)">
        <option value="Master of Veterinary Science (MVSc)">
        <option value="Master of Architecture (MArch)">
        <option value="Master of Public Administration (MPA)">
    </datalist>

    <datalist id="doctoralDegreeOptions">
        <option value="Doctor of Philosophy (PhD)">
        <option value="Doctor of Education (EdD)">
        <option value="Doctor of Musical Arts (DMA)">
        <option value="Doctor of Theology (ThD)">
        <option value="Doctor of Science (DSc/ScD)">
        <option value="Doctor of Arts (DA)">
        <option value="Doctor of Business Administration (DBA)">
        <option value="Doctor of Medicine (MD)">
        <option value="Doctor of Osteopathic Medicine (DO)">
        <option value="Doctor of Dental Surgery (DDS)">
        <option value="Doctor of Nursing Practice (DNP/DNSc)">
        <option value="Doctor of Pharmacy (PharmD)">
        <option value="Doctor of Podiatric Medicine (DPM)">
        <option value="Doctor of Physical Therapy (DPT)">
        <option value="Juris Doctor (JD)">
        <option value="Doctor of Juridical Science (JSD/SJD)">
        <option value="Doctor of Canon Law (JCD)">
        <option value="Doctor of Psychology (PsyD)">
        <option value="Doctor of Public Administration (DPA)">
        <option value="Doctor of Design (DDes)">
        <option value="Doctor of Fine Arts (DFA)">
        <option value="Doctor of Behavioral Health (DBH)">
        <option value="Doctor of Criminal Justice (DCJ)">
        <option value="Doctor of Information Technology (DIT)">
        <option value="Doctor of Social Work (DSW)">
        <option value="Doctor of Architecture (DArch)">
        <option value="Doctor of Professional Studies (DPS)">
        <option value="Doctor of Sustainability (DSus)">
    </datalist>

    <div id="doctoral-degrees-container">
    <div class="doctoral-degree-entry degree-card" data-degree-index="0">
    <h6 class="degree-card-title"><i class="bi bi-award"></i>Doctoral Degree</h6>
    <div class="mb-3 floating-input">
        <input type="text" class="form-select text-secondary doctoral-degree-select" id="doctoral_degree_0" name="doctoral_degrees[0][degree]" list="doctoralDegreeOptions" placeholder=" ">
        <label for="doctoral_degree_0">Doctoral Degree</label>
    </div>

    <div class="mb-3 floating-input year-field-transition year-hidden doctoral-school-wrapper">
        <input type="text" class="form-select doctoral-school-input" id="doctoral_school_name_0" name="doctoral_degrees[0][school_name]" placeholder=" ">
        <label for="doctoral_school_name_0">Doctoral School Name</label>
    </div>

    <div class="mb-3 floating-input year-field-transition year-hidden doctoral-year-wrapper">
        <select class="form-select text-secondary doctoral-year-select" id="doctoral_year_finished_0" name="doctoral_degrees[0][year_finished]">
            <option value="" selected style="color: #6c757d;">Select Year Finished</option>
            @for ($year = 2026; $year >= 1900; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </select>
        <label for="doctoral_year_finished_0">Doctoral Year Finished</label>
    </div>

    <div class="text-end">
        <button type="button" class="btn btn-outline-danger btn-sm remove-degree-btn remove-doctoral-degree d-none">Remove</button>
    </div>
    </div>
    </div>

    <div class="mb-3">
        <button type="button" id="addDoctoralDegreeBtn" class="btn btn-outline-success btn-sm add-entry-btn"><i class="bi bi-plus-circle me-1"></i>Add Another Doctoral Degree</button>
    </div>

    <div class="application-section-heading">
        <div>
            <span class="application-section-kicker"><i class="bi bi-briefcase"></i> Experience</span>
            <h4 class="mt-2">Work Experience</h4>
        </div>
    </div>

    <div class="work-experience-card mb-4">

    <div class="fresh-graduate-card mb-3">
        <input type="hidden" name="fresh_graduate" value="0">
        <input class="form-check-input" type="checkbox" id="fresh_graduate" name="fresh_graduate" value="1">
        <label class="form-check-label fw-semibold text-success mb-0" for="fresh_graduate">
            I am a Fresh Graduate (No work experience yet)
        </label>
    </div>

    <div class="work-field-grid mb-3">
    <div class="floating-input">
        <input type="text" class="form-select" id="work_position" name="work_position" placeholder=" " required>
        <label for="work_position">Position<span class="required-asterisk"> *</span></label>
    </div>

    <div class="floating-input">
        <input type="text" class="form-select" id="work_employer" name="work_employer" placeholder=" " required>
        <label for="work_employer">Employer<span class="required-asterisk"> *</span></label>
    </div>

    <div class="floating-input">
        <input type="text" class="form-select" id="work_location" name="work_location" placeholder=" " required>
        <label for="work_location">Location<span class="required-asterisk"> *</span></label>
    </div>

    <div class="floating-input">
        <input type="text" class="form-control" id="work_duration" name="work_duration" placeholder=" " required>
        <label for="work_duration">Duration<span class="required-asterisk"> *</span></label>
    </div>
    </div>

    <div class="mb-3 floating-input">
        <select class="form-select" id="experience_years" name="experience_years" required>
            <option value="" disabled selected></option>
            <option value="0–1">0–1</option>
            <option value="2–3">2–3</option>
            <option value="4–5">4–5</option>
            <option value="6+">6+</option>
        </select>
        <label for="experience_years">Years of Relevant Experience<span class="required-asterisk"> *</span></label>
    </div>

    <div class="skills-control-panel">
        <datalist id="skillsList">
            <option value="Team Leadership">
            <option value="Project Management">
            <option value="Communication">
            <option value="Software Development">
            <option value="Graphic Design">
            <option value="Data Analysis">
            <option value="Customer Service">
        </datalist>

        <h6 class="skills-toolbar-title"><i class="bi bi-stars"></i> Skills</h6>
        <input type="hidden" id="key_skills" name="key_skills" value="{{ old('key_skills') }}">
        <div class="skill-add-row">
            <div class="floating-input">
                <input list="skillsList" class="form-control" id="key_skill_input" placeholder=" " required>
                <label for="key_skill_input">Key Skill & Expertise<span class="required-asterisk"> *</span></label>
            </div>
            <button type="button" id="addSkillFieldBtn" class="btn add-skill-btn">
                <i class="bi bi-plus-circle me-1"></i>
                Add Skill
            </button>
        </div>
        <div id="skillChipList" class="skill-chip-list" aria-live="polite"></div>
    </div>
    </div>

    <div class="d-flex justify-content-between mt-auto step-actions">
        <button type="button" id="btnBackToPersonal" class="btn btn-secondary">Previous</button>
        <button type="button" id="btnToDocuments" class="btn btn-primary">Proceed</button>
    </div>

</div>


    <!--Documents Form-->
 <div id="documentsForm" class="mt-4 d-none form-step">
    <h4 class="fw-bold mb-3">Required Document</h4>

    <!-- Resume/CV -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Resume/CV <span class="required-asterisk"> *</span></label>
        <label class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your resume</div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB)</div>
            <input type="file" id="resume" data-document-index="0" accept=".pdf,.doc,.docx" required>
            <input type="hidden" name="documents[0][type]" value="Resume/CV">
        </label>
    </div>

    <!-- Cover Letter -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Cover Letter <span class="required-asterisk"> *</span></label>
        <label class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your cover letter</div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB)</div>
            <input type="file" id="cover_letter" data-document-index="1" accept=".pdf,.doc,.docx" required>
            <input type="hidden" name="documents[1][type]" value="Cover Letter">
        </label>
    </div>

    <!-- Transcript Of Records -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Transcript Of Records <span class="required-asterisk"> *</span></label>
        <label class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your Transcript Of Records</div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB)</div>
            <input type="file" id="TOR" data-document-index="3" accept=".pdf,.doc,.docx" required>
            <input type="hidden" name="documents[3][type]" value="Transcript Of Records">
        </label>
    </div>

    <!-- Diploma, Master's, Doctorate -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Diploma, Master's (if available), Doctorate (if available)</label>
        <label class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your Diploma, Master's, Doctorate </div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB each)</div>
            <input type="file" id="diploma" data-document-index="4" accept=".pdf,.doc,.docx" multiple>
            <input type="hidden" name="documents[4][type]" value="Diploma">
        </label>
    </div>

    <!-- PRC License/Board Rating -->
    <div class="mb-4">
        <label class="form-label fw-semibold">PRC License/Board Rating (if Applicable)</label>
        <label  class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your PRC License/Board Rating</div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB each)</div>
            <input type="file" id="board_rating" data-document-index="5" accept=".pdf,.doc,.docx" multiple>
            <input type="hidden" name="documents[5][type]" value="PRC License/Board Rating">
        </label>
    </div>

    <!-- Certificate Of Eligibility / Certificate of Passing -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Certificate Of Eligibility / Certificate of Passing (If Applicable)</label>
        <label  class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your Certificate Of Eligibility / Certificate of Passing</div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB each)</div>
            <input type="file" id="certification_eligibility" data-document-index="6" accept=".pdf,.doc,.docx" multiple>
            <input type="hidden" name="documents[6][type]" value="Certificate Of Eligibility / Certificate of Passing">
        </label>
    </div>

    <!-- Certifications -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Certifications & Supporting Document <span class="required-asterisk"> *</span></label>
        <label class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your documents</div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB each)</div>
            <input type="file" id="certifications" data-document-index="7" accept=".pdf,.doc,.docx" multiple required>
            <input type="hidden" name="documents[7][type]" value="Certifications & Supporting Document">
        </label>
    </div>

    <!-- Membership/Affiliation -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Membership/affiliation (If Applicable)</label>
        <label class="upload-area">
            <i class="bi bi-file-earmark-arrow-up upload-icon"></i>
            <div class="upload-main-text">Click to upload your documents</div>
            <div class="upload-sub-text">PDF, DOC, DOCX (up to 5MB each)</div>
            <input type="file" id="membership_affiliation" data-document-index="8" accept=".pdf,.doc,.docx" multiple>
            <input type="hidden" name="documents[8][type]" value="Membership/Affiliation">
        </label>
    </div>

    <div class="d-flex justify-content-between step-actions">
        <button type="button" id="btnBackToExperience" class="btn btn-secondary">Previous</button>
        <button type="button" id="btnToReview" class="btn btn-primary">Proceed</button>
    </div>
</div>




        <!-- Review & Submit Form (to be implemented) -->
    <!-- Review Your Application Form -->
    <div id="reviewForm" class="mt-4 d-none form-step">
        <h3 class="fw-bold mb-3">Review Your Application</h3>

        <div class="review-notice d-flex align-items-start mb-4">
            <div class="review-icon">i</div>
            <div class="ms-3">
                <div class="fw-semibold" style="font-size: 1.1rem;">Before you submit</div>
                <div class="text-dark-green">
                    Please review all information carefully. You can go back to any previous step to make sure changes.
                </div>
            </div>
        </div>

        <!-- Personal Information Summary -->
        <div class="mb-4 review-section-card">
            <h5 class="review-section-title"><i class="bi bi-person-badge"></i> Personal Information</h5>

            <div class="review-summary-grid">
                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-person"></i> Name</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">First Name</span>
                            <span id="review-first-name" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Middle Name</span>
                            <span id="review-middle-name" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Surname</span>
                            <span id="review-last-name" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Extension</span>
                            <span id="review-name-extension" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-envelope"></i> Contact</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Email</span>
                            <span id="review-email" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Phone</span>
                            <span id="review-phone" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-card-list"></i> Details</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Sex</span>
                            <span id="review-sex" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Civil Status</span>
                            <span id="review-civil-status" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Date of Birth</span>
                            <span id="review-date-of-birth" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-geo-alt"></i> Address</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Complete Address</span>
                            <span id="review-address" class="review-value"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Education & Experience Summary -->
        <div class="mb-4 review-section-card">
            <h5 class="review-section-title"><i class="bi bi-mortarboard"></i> Education & Experience</h5>

            <div class="review-summary-grid">
                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-building"></i> Basic Education</h6>
                    <div id="review-basic-education" class="review-item-list"></div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-award"></i> Degrees</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Bachelor</span>
                            <span id="review-bachelor-degree" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Master</span>
                            <span id="review-master-degree" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Doctoral</span>
                            <span id="review-doctoral-degree" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-briefcase"></i> Work Experience</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Position</span>
                            <span id="work_po" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Employer</span>
                            <span id="work_em" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Location</span>
                            <span id="work_lo" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Duration</span>
                            <span id="work_du" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Experience</span>
                            <span id="review-experience-years" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-stars"></i> Skills</h6>
                    <div id="review-key-skills" class="review-chip-list"></div>
                </div>
            </div>
        </div>


        <!-- Documents Summary -->
        <div class="mb-4 review-section-card">
            <h5 class="review-section-title"><i class="bi bi-folder-check"></i> Documents</h5>

            <div class="review-summary-grid">
                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-file-earmark-person"></i> Application Files</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Resume/CV</span>
                            <span id="review-resume-file" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Cover Letter</span>
                            <span id="review-cover-file" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-mortarboard"></i> Academic Records</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Transcript</span>
                            <span id="tor" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Diploma / Graduate</span>
                            <span id="dip" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-patch-check"></i> Licenses</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">PRC / Board Rating</span>
                            <span id="prc" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Eligibility / Passing</span>
                            <span id="passing" class="review-value"></span>
                        </div>
                    </div>
                </div>

                <div class="review-summary-panel">
                    <h6 class="review-panel-title"><i class="bi bi-award"></i> Other Proofs</h6>
                    <div class="review-item-list">
                        <div class="review-item">
                            <span class="review-label">Certifications</span>
                            <span id="review-certs-file" class="review-value"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Membership</span>
                            <span id="membership" class="review-value"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>




    <!-- Certification Checkbox -->
    <div class="review-notice1 d-flex align-items-start mb-4">
        <div class="form-check mb-3">
            <input
                class="form-check-input"
                type="checkbox"
                id="certifyCheckbox"
                required
            >
            <label class="form-check-label text-secondary" for="certifyCheckbox">
                I certify that all information provided is true and accurate to the best of my knowledge.
                I understand that any false information may result in disqualification.
            </label>
        </div>
    </div>




        <div class="d-flex justify-content-between step-actions">
            <button type="button" id="btnBackToDocumentsFromReview" class="btn btn-secondary">Previous</button>
            <button type="submit" class="btn btn-success">Submit Application</button>
        </div>
    </div>
</form>


        </div>
    </div>
</main>

<!-- JS for Dynamic File Name Display with Truncation -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('#documentsForm input[type="file"]');
    const applicationForm = document.getElementById('formPersonal');
    const documentDraftUploadUrl = @json(route('applicant.document.draft'));
    const documentDraftPosition = applicationForm?.querySelector('input[name="position"]')?.value ?? 'unknown';
    const documentDraftStorageKey = `non_teaching_document_drafts:${window.location.pathname}:${documentDraftPosition}`;
    const documentDraftKeyStorageKey = `${documentDraftStorageKey}:key`;
    let documentDraftKey = localStorage.getItem(documentDraftKeyStorageKey);
    if (!documentDraftKey) {
        documentDraftKey = (window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`).replace(/[^A-Za-z0-9_-]/g, '');
        localStorage.setItem(documentDraftKeyStorageKey, documentDraftKey);
    }
    let documentDrafts = {};
    let documentDraftUploadTasks = {};
    let documentDraftUploadQueue = Promise.resolve();

    try {
        documentDrafts = JSON.parse(localStorage.getItem(documentDraftStorageKey) || '{}') || {};
    } catch (_) {
        documentDrafts = {};
    }

    let draftHiddenContainer = document.getElementById('documentDraftHiddenInputs');
    if (!draftHiddenContainer && applicationForm) {
        draftHiddenContainer = document.createElement('div');
        draftHiddenContainer.id = 'documentDraftHiddenInputs';
        draftHiddenContainer.className = 'd-none';
        applicationForm.appendChild(draftHiddenContainer);
    }

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const documentIndexFromInput = (input) => {
        if (input.dataset.documentIndex) {
            return input.dataset.documentIndex;
        }

        const match = String(input.name || '').match(/documents\[(\d+)]/);
        return match ? match[1] : null;
    };

    const documentTypeFromInput = (input) => {
        const wrapper = input.closest('.upload-area');
        return wrapper?.querySelector('input[type="hidden"][name$="[type]"]')?.value || '';
    };

    function saveDocumentDrafts() {
        try {
            localStorage.setItem(documentDraftStorageKey, JSON.stringify(documentDrafts));
        } catch (_) {
            // Ignore storage errors.
        }
    }

    function sanitizeDraftFile(file) {
        return {
            type: String(file?.type || ''),
            filename: String(file?.filename || ''),
            filepath: String(file?.filepath || ''),
            mime_type: String(file?.mime_type || ''),
            size: Number(file?.size || 0),
        };
    }

    function clearDocumentDraftStorageForNextApplication() {
        try {
            localStorage.removeItem(documentDraftStorageKey);
            localStorage.removeItem(documentDraftKeyStorageKey);
        } catch (_) {
            // Ignore storage errors.
        }
    }

    window.clearDocumentDraftStorageForNextApplication = clearDocumentDraftStorageForNextApplication;

    function syncDocumentDraftInputs() {
        if (!draftHiddenContainer) return;

        draftHiddenContainer.textContent = '';
        Object.entries(documentDrafts).forEach(([index, files]) => {
            documentDrafts[index] = (files || []).map(sanitizeDraftFile);
            documentDrafts[index].forEach((file) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `draft_documents[${index}][]`;
                input.value = JSON.stringify(file);
                draftHiddenContainer.appendChild(input);
            });
        });
        saveDocumentDrafts();
    }

    function hasDocumentDraftForIndex(index) {
        return (documentDrafts[String(index)] || []).length > 0;
    }

    async function waitForDocumentDraftUploads() {
        const tasks = Object.values(documentDraftUploadTasks);
        if (tasks.length) {
            await Promise.allSettled(tasks);
        }
        syncDocumentDraftInputs();
    }

    window.hasDocumentDraftForIndex = hasDocumentDraftForIndex;
    window.waitForDocumentDraftUploads = waitForDocumentDraftUploads;

    function draftFilesLabel(files) {
        const names = (files || []).map((file) => file.filename).filter(Boolean);
        if (names.length > 1) return `${names.length} files uploaded`;
        if (!names.length) return '';

        let fileName = names[0];
        if (fileName.length > 30) {
            const ext = fileName.split('.').pop();
            fileName = fileName.substring(0, 25) + '...' + '.' + ext;
        }

        return fileName;
    }

    fileInputs.forEach(input => {
        const uploadArea = input.closest('.upload-area');
        const uploadText = uploadArea.querySelector('.upload-main-text');
        const uploadSubText = uploadArea.querySelector('.upload-sub-text');
        const defaultMainText = uploadText.textContent;
        const defaultSubText = uploadSubText.textContent;
        const defaultRequired = input.required;
        const documentIndex = documentIndexFromInput(input);
        const clearButton = document.createElement('button');

        clearButton.type = 'button';
        clearButton.className = 'upload-clear-btn';
        clearButton.setAttribute('aria-label', 'Remove selected file');
        clearButton.textContent = 'x';
        uploadArea.appendChild(clearButton);

        function resetUploadState() {
            input.value = '';
            if (documentIndex !== null) {
                delete documentDrafts[documentIndex];
                saveDocumentDrafts();
                syncDocumentDraftInputs();
            }
            input.required = defaultRequired;
            uploadText.textContent = defaultMainText;
            uploadSubText.textContent = defaultSubText;
            uploadArea.classList.remove('is-selected');
        }

        function selectedFileSummary(files) {
            const selectedFiles = Array.from(files || []);
            if (selectedFiles.length > 1) {
                return `${selectedFiles.length} files selected`;
            }

            let fileName = selectedFiles[0]?.name || '';
            if (fileName.length > 30) {
                const ext = fileName.split('.').pop();
                fileName = fileName.substring(0, 25) + '...' + '.' + ext;
            }

            return fileName;
        }

        function setSelectedState(files) {
            uploadText.textContent = selectedFileSummary(files);
            uploadSubText.textContent = files.length > 1 ? 'Files selected successfully' : 'File selected successfully';
            uploadArea.classList.add('is-selected');
        }

        function restoreDraftState() {
            const draftFiles = documentIndex !== null ? (documentDrafts[documentIndex] || []) : [];
            if (!draftFiles.length) return;

            input.required = false;
            uploadText.textContent = draftFilesLabel(draftFiles);
            uploadSubText.textContent = draftFiles.length > 1 ? 'Files saved from draft' : 'File saved from draft';
            uploadArea.classList.add('is-selected');
        }

        async function uploadDocumentDraft(files) {
            if (documentIndex === null || !files.length) return;

            const selectedFiles = Array.from(files);
            const invalidFile = selectedFiles.find((file) => {
                const extension = file.name.split('.').pop()?.toLowerCase();
                return !['pdf', 'doc', 'docx'].includes(extension || '') || file.size > 5 * 1024 * 1024;
            });

            if (invalidFile) {
                throw new Error('Only PDF, DOC, or DOCX files up to 5MB each are allowed.');
            }

            const formData = new FormData();
            formData.append('draft_key', documentDraftKey);
            formData.append('document_index', documentIndex);
            formData.append('document_type', documentTypeFromInput(input));
            selectedFiles.forEach((file) => formData.append('draft_files[]', file));

            uploadSubText.textContent = 'Saving file draft...';
            const controller = new AbortController();
            const timeoutId = window.setTimeout(() => controller.abort(), 60000);

            let response;
            try {
                response = await fetch(documentDraftUploadUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                    signal: controller.signal,
                });
            } catch (error) {
                throw new Error(error.name === 'AbortError'
                    ? 'Upload timed out. Please choose a smaller file or try again.'
                    : 'Unable to connect while saving this file.');
            } finally {
                window.clearTimeout(timeoutId);
            }

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload?.message || `Unable to save file draft. Server returned ${response.status}.`);
            }

            documentDrafts[documentIndex] = payload.files || [];
            saveDocumentDrafts();
            syncDocumentDraftInputs();
            input.required = false;
            uploadSubText.textContent = (payload.files || []).length > 1 ? 'Files saved from draft' : 'File saved from draft';
            uploadArea.classList.remove('field-error-highlight');
        }

        input.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                setSelectedState(this.files);
                const selectedFiles = Array.from(this.files);
                const uploadTask = documentDraftUploadQueue
                    .catch(() => {})
                    .then(() => uploadDocumentDraft(selectedFiles))
                    .catch((error) => {
                        uploadSubText.textContent = error.message || 'Selected, but draft was not saved';
                        uploadArea.classList.add('field-error-highlight');
                        delete documentDrafts[documentIndex];
                        syncDocumentDraftInputs();
                    })
                    .finally(() => {
                        delete documentDraftUploadTasks[documentIndex];
                    });
                documentDraftUploadQueue = uploadTask;
                documentDraftUploadTasks[documentIndex] = uploadTask;
                return;
            }

            resetUploadState();
        });

        clearButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            resetUploadState();
        });

        restoreDraftState();
    });

    syncDocumentDraftInputs();

    const intakeUploadZone = document.getElementById('intakeUploadZone');
    const intakeUploadInput = document.getElementById('intakeUploadInput');
    const intakeUploadTitle = document.getElementById('intakeUploadTitle');
    const intakeUploadSubtitle = document.getElementById('intakeUploadSubtitle');
    const scanUploadedFileButton = document.getElementById('scanUploadedFileButton');
    const clearIntakeUploadButton = document.getElementById('clearIntakeUploadButton');
    const scanProgressBar = document.getElementById('scanProgressBar');
    const scanStateLabel = document.getElementById('scanStateLabel');
    const autoSaveLabel = document.getElementById('autoSaveLabel');
    const scanCheckFile = document.getElementById('scanCheckFile');
    const scanCheckScan = document.getElementById('scanCheckScan');
    const pdsRecordInput = document.getElementById('pds_record_id');
    const btnToPersonalForScan = document.getElementById('btnToPersonal');
    const pdsScanUrl = @json(route('applicant.pds.scan'));
    let isScanningPds = false;

    function setScanItemComplete(item) {
        item?.classList.add('is-complete');
        const icon = item?.querySelector('i');
        if (icon) icon.className = 'bi bi-check-circle-fill';
    }

    function setScanItemPending(item) {
        item?.classList.remove('is-complete');
        const icon = item?.querySelector('i');
        if (icon) icon.className = 'bi bi-circle';
    }

    function syncContinueButtonWithScan() {
        const hasCompletedScan = Boolean(pdsRecordInput?.value)
            && scanCheckScan?.classList.contains('is-complete');
        if (!btnToPersonalForScan) return;

        btnToPersonalForScan.disabled = !hasCompletedScan;
        btnToPersonalForScan.classList.toggle('disabled', !hasCompletedScan);
        btnToPersonalForScan.title = hasCompletedScan
            ? ''
            : 'Scan your Personal Data Sheet before continuing';
    }

    function resetIntakeScanDesign() {
        if (intakeUploadInput) intakeUploadInput.value = '';
        if (pdsRecordInput) pdsRecordInput.value = '';
        intakeUploadZone?.classList.remove('is-ready');
        if (intakeUploadTitle) intakeUploadTitle.textContent = 'Upload Personal Data Sheet';
        if (intakeUploadSubtitle) intakeUploadSubtitle.textContent = 'Excel files only: XLS, XLSX, CSV';
        if (scanStateLabel) scanStateLabel.textContent = 'Waiting for upload';
        if (autoSaveLabel) {
            autoSaveLabel.textContent = 'Not saved';
            autoSaveLabel.className = 'badge rounded-pill bg-light text-secondary';
        }
        if (scanProgressBar) scanProgressBar.style.width = '0%';
        [scanCheckFile, scanCheckScan].forEach(setScanItemPending);
        syncContinueButtonWithScan();
    }

    intakeUploadInput?.addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file) {
            resetIntakeScanDesign();
            return;
        }

        intakeUploadZone?.classList.add('is-ready');
        intakeUploadTitle.textContent = file.name;
        intakeUploadSubtitle.textContent = 'Personal Data Sheet ready to scan';
        scanStateLabel.textContent = 'Personal Data Sheet uploaded';
        autoSaveLabel.textContent = 'Pending scan';
        autoSaveLabel.className = 'badge rounded-pill bg-warning-subtle text-warning';
        scanProgressBar.style.width = '25%';
        if (pdsRecordInput) pdsRecordInput.value = '';
        setScanItemComplete(scanCheckFile);
        setScanItemPending(scanCheckScan);
        syncContinueButtonWithScan();
    });

    function fillFieldFromPds(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        field.value = value || '';
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function fillSelectFromPds(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        if (!value) {
            field.value = '';
            field.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        const normalizedValue = String(value).trim().toLowerCase();
        const option = Array.from(field.options).find((item) =>
            item.value.toLowerCase() === normalizedValue || item.textContent.trim().toLowerCase() === normalizedValue
        );
        if (!option) return;

        field.value = option.value;
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function isPdsCitizenshipCountryValue(value) {
        const normalized = String(value || '')
            .toLowerCase()
            .replace(/[-\/.:#,]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        return [
            'afghanistan',
            'albania',
            'algeria',
            'andorra',
            'angola',
            'argentina',
            'armenia',
            'australia',
            'austria',
            'bahamas',
            'bahamas the',
            'bahrain',
            'bangladesh',
            'belgium',
            'brazil',
            'canada',
            'china',
            'denmark',
            'dual citizenship',
            'filipino',
            'france',
            'germany',
            'india',
            'indonesia',
            'italy',
            'japan',
            'malaysia',
            'philippines',
            'singapore',
            'spain',
            'thailand',
            'united states',
            'united states of america',
        ].includes(normalized);
    }

    function pdsAddressValue(value) {
        return isPdsCitizenshipCountryValue(value) ? '' : value;
    }

    function pdsNameExtensionValue(value) {
        const rawValue = String(value || '').trim();
        const normalized = rawValue
            .toLowerCase()
            .replace(/[.,()]/g, ' ')
            .replace(/[-\/:#]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (!normalized || [
            'name extension',
            'name extension jr sr',
            'jr sr',
            'na',
            'n a',
            'none',
            'not applicable',
        ].includes(normalized)) {
            return '';
        }

        return /[a-z0-9]/i.test(rawValue) ? rawValue : '';
    }

    function pdsEducationValue(value) {
        const rawValue = String(value || '').trim();
        const normalized = rawValue
            .toLowerCase()
            .replace(/[()']/g, ' ')
            .replace(/[-\/.:#,]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (!normalized || [
            'congo republic of the',
            'costa rica',
            'cote d ivoire',
            'croatia',
            'cuba',
            'curacao',
            'elementary',
            'secondary',
            'vocational',
            'trade course',
            'vocational trade course',
            'college',
            'graduate studies',
            'name of school',
            'write in full',
            'basic education degree course',
            'period of attendance',
            'from',
            'to',
            'highest level units earned',
            'if not graduated',
            'year graduated',
            'scholarship academic honors received',
            'continue on separate sheet if necessary',
            'na',
            'n a',
            'none',
            'not applicable',
        ].includes(normalized)) {
            return '';
        }

        return /[a-z0-9]/i.test(rawValue) ? rawValue : '';
    }

    function fillEducationFromPds(fields = {}) {
        [
            ['elementary', 'elementary'],
            ['secondary', 'secondary'],
            ['vocational_trade', 'vocational_trade'],
            ['college', 'college'],
        ].forEach(([fieldPrefix, inputPrefix]) => {
            fillFieldFromPds(`${inputPrefix}_school_name`, pdsEducationValue(fields[`${fieldPrefix}_school_name`]));
            fillSelectFromPds(`${inputPrefix}_year_graduated`, pdsEducationValue(fields[`${fieldPrefix}_year_graduated`]));
        });

        fillFieldFromPds('bachelor_degree_0', pdsEducationValue(fields.college_degree));

        fillFieldFromPds('master_degree_0', fields.graduate_studies_degree);
        fillFieldFromPds('master_school_name_0', fields.graduate_studies_school_name);
        fillSelectFromPds('master_year_finished_0', fields.graduate_studies_year_graduated);
    }

    function fillVisibleFormFromPds(fields = {}) {
        const normalizeChoiceValue = (value) => String(value || '')
            .toLowerCase()
            .replace(/[^a-z]/g, '');

        const fillChoiceSelectFromPds = (fieldId, value) => {
            const field = document.getElementById(fieldId);
            if (!field || !value) return false;

            const normalizedValue = normalizeChoiceValue(value);
            const option = Array.from(field.options).find((option) => {
                const normalizedOptionValue = normalizeChoiceValue(option.value);
                const normalizedOptionText = normalizeChoiceValue(option.textContent);

                return normalizedOptionValue !== ''
                    && (
                        normalizedOptionValue === normalizedValue
                        || normalizedOptionText === normalizedValue
                        || normalizedOptionText.includes(normalizedValue)
                        || normalizedValue.includes(normalizedOptionValue)
                    );
            });

            if (!option) return false;

            field.value = option.value;
            field.dispatchEvent(new Event('change', { bubbles: true }));

            return true;
        };

        fillFieldFromPds('first_name', fields.first_name);
        fillFieldFromPds('middle_name', fields.middle_name);
        fillFieldFromPds('last_name', fields.surname);
        fillFieldFromPds('name_extension', pdsNameExtensionValue(fields.name_extension));
        fillFieldFromPds('email', fields.email_address);
        fillFieldFromPds('phone', fields.mobile_no || fields.telephone_no);
        fillFieldFromPds('date_of_birth', fields.date_of_birth);
        fillFieldFromPds('address', pdsAddressValue(fields.permanent_address || fields.permanent_address_zip_code));

        fillChoiceSelectFromPds('sex', fields.sex);
        fillChoiceSelectFromPds('civil_status', fields.civil_status);

        fillEducationFromPds(fields);
    }

    scanUploadedFileButton?.addEventListener('click', async function () {
        if (!intakeUploadInput?.files?.length) {
            scanStateLabel.textContent = 'Upload your Personal Data Sheet before scanning';
            return;
        }

        if (isScanningPds) return;

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value
            || '';
        const formData = new FormData();
        formData.append('pds_file', intakeUploadInput.files[0]);

        isScanningPds = true;
        scanUploadedFileButton.disabled = true;
        scanStateLabel.textContent = 'Scanning Personal Data Sheet...';
        autoSaveLabel.textContent = 'Scanning';
        autoSaveLabel.className = 'badge rounded-pill bg-info-subtle text-info';
        scanProgressBar.style.width = '65%';

        try {
            const response = await fetch(pdsScanUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload?.message || 'Unable to scan the Personal Data Sheet.');
            }

            if (pdsRecordInput) pdsRecordInput.value = payload.id || '';
            const scannedFields = payload.fields || {};
            scannedFields.sex = scannedFields.sex || (payload.choice_debug && payload.choice_debug.sex) || '';
            scannedFields.civil_status = scannedFields.civil_status || (payload.choice_debug && payload.choice_debug.civil_status) || '';
            fillVisibleFormFromPds(scannedFields);
            scanStateLabel.textContent = payload.message || 'Personal Data Sheet scan complete';
            scanProgressBar.style.width = '100%';
            autoSaveLabel.textContent = 'Saved to PDS table';
            autoSaveLabel.className = 'badge rounded-pill bg-success-subtle text-success';
            setScanItemComplete(scanCheckScan);
            syncContinueButtonWithScan();
        } catch (error) {
            if (pdsRecordInput) pdsRecordInput.value = '';
            scanStateLabel.textContent = error.message || 'Unable to scan the Personal Data Sheet.';
            scanProgressBar.style.width = '25%';
            autoSaveLabel.textContent = 'Scan failed';
            autoSaveLabel.className = 'badge rounded-pill bg-danger-subtle text-danger';
            setScanItemPending(scanCheckScan);
            syncContinueButtonWithScan();
        } finally {
            isScanningPds = false;
            scanUploadedFileButton.disabled = false;
        }
    });

    clearIntakeUploadButton?.addEventListener('click', resetIntakeScanDesign);
    syncContinueButtonWithScan();

});
</script>



<script>
document.addEventListener('DOMContentLoaded', () => {

    /* =======================
       FORM SECTIONS
    ======================= */
    const applicationFormStep = document.getElementById('applicationFormStep');
    const personalForm   = document.getElementById('personalForm');
    const experienceForm = document.getElementById('experienceForm');
    const documentsForm  = document.getElementById('documentsForm');
    const reviewForm     = document.getElementById('reviewForm');

    /* =======================
       BUTTONS
    ======================= */
    const btnToPersonal                = document.getElementById('btnToPersonal');
    const btnBackToApplication         = document.getElementById('btnBackToApplication');
    const btnToExperience              = document.getElementById('btnToExperience');
    const btnBackToPersonal            = document.getElementById('btnBackToPersonal');
    const btnToDocuments               = document.getElementById('btnToDocuments');
    const btnBackToExperience          = document.getElementById('btnBackToExperience');
    const btnToReview                  = document.getElementById('btnToReview');
    const btnBackToDocumentsFromReview = document.getElementById('btnBackToDocumentsFromReview');
    const intakeUploadZoneForNav       = document.getElementById('intakeUploadZone');
    const intakeUploadInputForNav      = document.getElementById('intakeUploadInput');
    const scanStateLabelForNav         = document.getElementById('scanStateLabel');
    const autoSaveLabelForNav          = document.getElementById('autoSaveLabel');
    const pdsRecordInputForNav         = document.getElementById('pds_record_id');

    /* =======================
       STEPPER ELEMENTS
    ======================= */
    const steps = document.querySelectorAll('.step1');
    const lines = document.querySelectorAll('.line1');
    const stepProgressBar = document.getElementById('stepProgressBar');
    const currentStepText = document.getElementById('currentStepText');

    const stepMeta = {
        1: 'Step 1 of 6: Application',
        2: 'Step 2 of 6: Personal Info',
        3: 'Step 3 of 6: Experience',
        4: 'Step 4 of 6: Documents',
        5: 'Step 5 of 6: Review',
        6: 'Step 6 of 6: Submit',
    };

    function setStep(stepNumber) {
        steps.forEach((step, index) => {
            step.classList.remove('active', 'completed1');
            if (index + 1 < stepNumber) step.classList.add('completed1');
            else if (index + 1 === stepNumber) step.classList.add('active');
        });

        lines.forEach((line, index) => {
            line.classList.toggle('completed1', index < stepNumber - 1);
        });

        if (stepProgressBar) {
            const maxStep = Math.max(1, steps.length);
            const progress = Math.min(Math.max(stepNumber, 1), maxStep);
            stepProgressBar.style.width = `${(progress / maxStep) * 100}%`;
        }

        if (currentStepText) {
            currentStepText.textContent = stepMeta[stepNumber] || stepMeta[1];
        }
    }

    setStep(1); // Initial step

    /* =======================
       CERTIFICATION CHECKBOX
    ======================= */
    const certifyCheckbox = document.getElementById('certifyCheckbox');
    const submitButton = reviewForm.querySelector('button[type="submit"]');
    const applicationForm = document.getElementById('formPersonal');
    submitButton.disabled = true;

    const positionInput = applicationForm?.querySelector('input[name="position"]');
    const formDraftStorageKey = `non_teaching_application_draft:${window.location.pathname}:${positionInput?.value ?? 'unknown'}`;

    const persistableFields = applicationForm
        ? Array.from(applicationForm.querySelectorAll('input[id][name], select[id][name], textarea[id][name]'))
            .filter((field) => {
                const type = (field.type || '').toLowerCase();
                return field.id !== 'pds_record_id' && type !== 'file' && type !== 'password';
            })
        : [];

    function saveFormDraft() {
        if (!applicationForm) return;

        const payload = {};
        persistableFields.forEach((field) => {
            payload[field.id] = (field.type || '').toLowerCase() === 'checkbox'
                ? field.checked
                : field.value;
        });

        try {
            localStorage.setItem(formDraftStorageKey, JSON.stringify(payload));
        } catch (_) {
            // Ignore storage errors (private mode/quota).
        }
    }

    function restoreFormDraft() {
        if (!applicationForm) return;

        let payload = null;
        try {
            payload = JSON.parse(localStorage.getItem(formDraftStorageKey) || 'null');
        } catch (_) {
            payload = null;
        }
        if (!payload || typeof payload !== 'object') return;

        persistableFields.forEach((field) => {
            if (!(field.id in payload)) return;
            const value = payload[field.id];
            if ((field.type || '').toLowerCase() === 'checkbox') {
                field.checked = Boolean(value);
            } else {
                field.value = value ?? '';
            }
        });
    }

    function clearFormDraft() {
        try {
            localStorage.removeItem(formDraftStorageKey);
            window.clearDocumentDraftStorageForNextApplication?.();
        } catch (_) {
            // Ignore storage errors.
        }
    }

    function documentDraftIndexesForFinalSubmit() {
        return new Set(Array.from(applicationForm.querySelectorAll('input[name^="draft_documents["]'))
            .map((input) => String(input.name || '').match(/draft_documents\[(\d+)]/))
            .filter(Boolean)
            .map((match) => match[1]));
    }

    function prepareFinalSubmitPayload() {
        // The scanned PDS is already saved in pds_table, so do not upload it again with the final application.
        const pdsInput = document.getElementById('intakeUploadInput');
        if (pdsInput) {
            pdsInput.disabled = true;
            pdsInput.removeAttribute('name');
        }

        applicationForm.querySelectorAll('#documentsForm input[type="file"]').forEach((input) => {
            // Document files are uploaded as drafts immediately after selection.
            // Final submit only needs the hidden draft_documents payload.
            input.disabled = true;
            input.removeAttribute('name');
        });
    }

    certifyCheckbox.addEventListener('change', () => {
        submitButton.disabled = !certifyCheckbox.checked;
    });

    function hasRequiredAsterisk(field) {
        if (!field) return false;

        const fieldType = (field.type || '').toLowerCase();
        if (fieldType === 'file') {
            const block = field.closest('.mb-4');
            const blockLabel = block?.querySelector(':scope > label.form-label');
            return !!(blockLabel && (blockLabel.querySelector('.required-asterisk') || blockLabel.textContent.includes('*')));
        }

        const fieldId = field.id || '';
        const explicitLabel = fieldId
            ? applicationForm.querySelector(`label[for="${fieldId}"]`)
            : null;

        return !!(explicitLabel && (explicitLabel.querySelector('.required-asterisk') || explicitLabel.textContent.includes('*')));
    }

    function getErrorHighlightTarget(field) {
        if (!field) return null;

        if ((field.type || '').toLowerCase() === 'file') {
            return field.closest('.upload-area') || field;
        }
        if ((field.type || '').toLowerCase() === 'checkbox') {
            return field.closest('.review-notice1') || field.closest('.form-check') || field;
        }

        return field.closest('.floating-input')
            || field.closest('.mb-3')
            || field.closest('.col-md-6')
            || field;
    }

    function clearErrorHighlight(field) {
        const target = getErrorHighlightTarget(field);
        if (target) target.classList.remove('field-error-highlight');
    }

    function showErrorHighlight(field) {
        const target = getErrorHighlightTarget(field);
        if (!target) return;
        target.classList.remove('field-error-highlight');
        // force reflow so animation retriggers
        void target.offsetWidth;
        target.classList.add('field-error-highlight');
    }

    function showStepFormForField(field) {
        if (!field) return;

        const isInPersonal = !!field.closest('#personalForm');
        const isInExperience = !!field.closest('#experienceForm');
        const isInDocuments = !!field.closest('#documentsForm');
        const isInReview = !!field.closest('#reviewForm');

        applicationFormStep.classList.add('d-none');
        personalForm.classList.add('d-none');
        experienceForm.classList.add('d-none');
        documentsForm.classList.add('d-none');
        reviewForm.classList.add('d-none');

        if (isInPersonal) {
            personalForm.classList.remove('d-none');
            setStep(2);
            return;
        }
        if (isInExperience) {
            experienceForm.classList.remove('d-none');
            setStep(3);
            return;
        }
        if (isInDocuments) {
            documentsForm.classList.remove('d-none');
            setStep(4);
            return;
        }
        if (isInReview) {
            reviewForm.classList.remove('d-none');
            setStep(5);
            return;
        }

        reviewForm.classList.remove('d-none');
        setStep(5);
    }

    if (applicationForm) {
        applicationForm.setAttribute('novalidate', 'novalidate');
        const csrfRefreshUrl = @json(route('csrf.token'));
        const nativeSubmit = HTMLFormElement.prototype.submit.bind(applicationForm);
        let isSubmittingApplication = false;

        const refreshCsrfToken = async () => {
            const tokenInput = applicationForm.querySelector('input[name="_token"]');
            if (!tokenInput || !csrfRefreshUrl) return;

            try {
                const response = await fetch(csrfRefreshUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) return;

                const payload = await response.json();
                if (payload?.token) {
                    tokenInput.value = payload.token;
                    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', payload.token);
                }
            } catch (error) {
                // Continue with the rendered token if the refresh request is unavailable.
            }
        };

        applicationForm.querySelectorAll('[required]').forEach((field) => {
            if (!hasRequiredAsterisk(field)) return;
            field.addEventListener('input', () => clearErrorHighlight(field));
            field.addEventListener('change', () => clearErrorHighlight(field));
        });

        applicationForm.addEventListener('submit', async (event) => {
            if (isSubmittingApplication) return;

            event.preventDefault();
            await window.waitForDocumentDraftUploads?.();

            const requiredFields = Array.from(applicationForm.querySelectorAll('[required]'))
                .filter((field) => !field.disabled && hasRequiredAsterisk(field));

            const invalidFields = requiredFields.filter((field) => {
                const type = (field.type || '').toLowerCase();
                if (type === 'file') {
                    const documentIndex = field.dataset?.documentIndex;
                    if (documentIndex) {
                        return !window.hasDocumentDraftForIndex?.(documentIndex);
                    }

                    return !(field.files && field.files.length > 0);
                }
                if (type === 'checkbox') return !field.checked;
                return !field.checkValidity();
            });

            if (!invalidFields.length) {
                isSubmittingApplication = true;
                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';
                await refreshCsrfToken();
                prepareFinalSubmitPayload();
                clearFormDraft();
                nativeSubmit();
                return;
            }

            invalidFields.forEach((field) => showErrorHighlight(field));

            const firstInvalid = invalidFields[0];
            showStepFormForField(firstInvalid);
            const firstTarget = getErrorHighlightTarget(firstInvalid) || firstInvalid;
            setTimeout(() => {
                if (firstTarget && typeof firstTarget.scrollIntoView === 'function') {
                    firstTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                if (firstInvalid && typeof firstInvalid.focus === 'function') {
                    firstInvalid.focus({ preventScroll: true });
                }
                showErrorHighlight(firstInvalid);
            }, 40);
        });
    }

    restoreFormDraft();
    const scanCheckScanForNav = document.getElementById('scanCheckScan');
    const hasCompletedCurrentPdsScan = () => Boolean(pdsRecordInputForNav?.value)
        && scanCheckScanForNav?.classList.contains('is-complete');
    if (btnToPersonal) {
        btnToPersonal.disabled = !hasCompletedCurrentPdsScan();
        btnToPersonal.classList.toggle('disabled', !hasCompletedCurrentPdsScan());
    }
    submitButton.disabled = !certifyCheckbox.checked;
    persistableFields.forEach((field) => {
        field.addEventListener('input', saveFormDraft);
        field.addEventListener('change', saveFormDraft);
    });

    /* =======================
       FORM TRANSITION FUNCTION
    ======================= */
    function transitionForms(hideForm, showForm, direction = 'forward') {
        const outClass = direction === 'forward' ? 'slide-out-left' : 'slide-out-right';
        const inClass  = direction === 'forward' ? 'slide-in-right' : 'slide-in-left';

        hideForm.classList.add(outClass);

        setTimeout(() => {
            hideForm.classList.add('d-none');
            hideForm.classList.remove(outClass);

            showForm.classList.remove('d-none');
            showForm.classList.add(inClass);

            setTimeout(() => {
                showForm.classList.remove(inClass);
            }, 450);
        }, 300);
    }

    /* =======================
       NAVIGATION LOGIC
    ======================= */

    // Step 1 → Step 2
    btnToPersonal.addEventListener('click', () => {
        if (!hasCompletedCurrentPdsScan()) {
            scanStateLabelForNav.textContent = intakeUploadInputForNav?.files?.length
                ? 'Please scan the uploaded Personal Data Sheet before continuing'
                : 'Please upload and scan your Personal Data Sheet before continuing';
            autoSaveLabelForNav.textContent = 'Scan required';
            autoSaveLabelForNav.className = 'badge rounded-pill bg-danger-subtle text-danger';
            intakeUploadZoneForNav?.classList.add('field-error-highlight');
            setTimeout(() => intakeUploadZoneForNav?.classList.remove('field-error-highlight'), 1400);
            return;
        }

        transitionForms(applicationFormStep, personalForm, 'forward');
        setStep(2);
    });

    btnBackToApplication.addEventListener('click', () => {
        transitionForms(personalForm, applicationFormStep, 'back');
        setStep(1);
    });

    btnToExperience.addEventListener('click', () => {
        transitionForms(personalForm, experienceForm, 'forward');
        setStep(3);
    });

    // Step 2 → Step 1
    btnBackToPersonal.addEventListener('click', () => {
        transitionForms(experienceForm, personalForm, 'back');
        setStep(2);
    });

    // Step 2 → Step 3
    btnToDocuments.addEventListener('click', () => {
        transitionForms(experienceForm, documentsForm, 'forward');
        setStep(4);
    });

    // Step 3 → Step 2
    btnBackToExperience.addEventListener('click', () => {
        transitionForms(documentsForm, experienceForm, 'back');
        setStep(3);
    });

    // Step 3 → Step 4 (Review)
    btnToReview.addEventListener('click', () => {
        // Populate review fields
        document.getElementById('review-first-name').textContent = document.getElementById('first_name').value;
        document.getElementById('review-middle-name').textContent = document.getElementById('middle_name').value || 'N/A';
        document.getElementById('review-last-name').textContent = document.getElementById('last_name').value;
        document.getElementById('review-name-extension').textContent = document.getElementById('name_extension').value || 'N/A';
        document.getElementById('review-email').textContent = document.getElementById('email').value;
        document.getElementById('review-phone').textContent = document.getElementById('phone').value;
        document.getElementById('review-sex').textContent = document.getElementById('sex').value;
        document.getElementById('review-civil-status').textContent = document.getElementById('civil_status').value;
        document.getElementById('review-date-of-birth').textContent = document.getElementById('date_of_birth').value;
        document.getElementById('review-address').textContent = document.getElementById('address').value;

        const basicEducationLabels = {
            elementary: 'Elementary',
            secondary: 'Secondary',
            vocational_trade: 'Vocational / Trade Course',
            college: 'College',
        };
        const basicEducationLines = Object.entries(basicEducationLabels).map(([key, label]) => {
            const school = document.getElementById(`${key}_school_name`)?.value || 'N/A';
            const year = document.getElementById(`${key}_year_graduated`)?.value || 'N/A';
            return { label, value: `${school} (${year})` };
        });
        const basicEducationReview = document.getElementById('review-basic-education');
        basicEducationReview.textContent = '';
        basicEducationLines.forEach((item) => {
            const lineItem = document.createElement('div');
            lineItem.className = 'review-item';

            const label = document.createElement('span');
            label.className = 'review-label';
            label.textContent = item.label;

            const value = document.createElement('span');
            value.className = 'review-value';
            value.textContent = item.value;

            lineItem.appendChild(label);
            lineItem.appendChild(value);
            basicEducationReview.appendChild(lineItem);
        });

        const bachelorEntries = Array.from(document.querySelectorAll('.bachelor-degree-entry'));
        const masterEntries = Array.from(document.querySelectorAll('.master-degree-entry'));
        const doctoralEntries = Array.from(document.querySelectorAll('.doctoral-degree-entry'));

        const formatDegreeReview = (degree, school, year) => {
            if (!degree) return 'N/A';
            const schoolLabel = school || 'School not provided';
            const yearLabel = year || 'Year not provided';
            return `${degree} - ${schoolLabel} (${yearLabel})`;
        };

        const bachelorReviewLines = bachelorEntries
            .map((entry) => {
                const degree = (entry.querySelector('.bachelor-degree-select')?.value || '').trim();
                const school = (entry.querySelector('.bachelor-school-input')?.value || '').trim();
                const year = (entry.querySelector('.bachelor-year-select')?.value || '').trim();
                return formatDegreeReview(degree, school, year);
            })
            .filter((line) => line !== 'N/A');

        const masterReviewLines = masterEntries
            .map((entry) => {
                const degree = (entry.querySelector('.master-degree-select')?.value || '').trim();
                const school = (entry.querySelector('.master-school-input')?.value || '').trim();
                const year = (entry.querySelector('.master-year-select')?.value || '').trim();
                return formatDegreeReview(degree, school, year);
            })
            .filter((line) => line !== 'N/A');

        const doctoralReviewLines = doctoralEntries
            .map((entry) => {
                const degree = (entry.querySelector('.doctoral-degree-select')?.value || '').trim();
                const school = (entry.querySelector('.doctoral-school-input')?.value || '').trim();
                const year = (entry.querySelector('.doctoral-year-select')?.value || '').trim();
                return formatDegreeReview(degree, school, year);
            })
            .filter((line) => line !== 'N/A');

        document.getElementById('review-bachelor-degree').textContent =
            bachelorReviewLines.length ? bachelorReviewLines.join(' | ') : 'N/A';
        document.getElementById('review-master-degree').textContent =
            masterReviewLines.length ? masterReviewLines.join(' | ') : 'N/A';
        document.getElementById('review-doctoral-degree').textContent =
            doctoralReviewLines.length ? doctoralReviewLines.join(' | ') : 'N/A';

        syncKeySkillsValue();
        document.getElementById('review-experience-years').textContent = document.getElementById('experience_years').value;
        const reviewSkills = document.getElementById('review-key-skills');
        reviewSkills.textContent = '';
        const skillValues = document.getElementById('key_skills').value
            .split(',')
            .map((skill) => skill.trim())
            .filter(Boolean);
        if (skillValues.length) {
            skillValues.forEach((skill) => {
                const chip = document.createElement('span');
                chip.className = 'review-chip';
                chip.textContent = skill;
                reviewSkills.appendChild(chip);
            });
        } else {
            const emptySkill = document.createElement('span');
            emptySkill.className = 'review-value';
            emptySkill.textContent = 'N/A';
            reviewSkills.appendChild(emptySkill);
        }

        document.getElementById('work_po').textContent = document.getElementById('work_position').value;
        document.getElementById('work_em').textContent = document.getElementById('work_employer').value;
        document.getElementById('work_lo').textContent = document.getElementById('work_location').value;
        document.getElementById('work_du').textContent = document.getElementById('work_duration').value;

        const resumeInput = document.getElementById('resume');
        const coverInput  = document.getElementById('cover_letter');
        const certsInput  = document.getElementById('certifications');
        const torInput  = document.getElementById('TOR');
        const diplomaInput  = document.getElementById('diploma');
        const boardRatingInput = document.getElementById('board_rating');
        const certificateEligibilityInput  = document.getElementById('certification_eligibility');
        const membershipInput  = document.getElementById('membership_affiliation');

        const documentIndexFromReviewInput = (input) => {
            if (input?.dataset?.documentIndex) {
                return input.dataset.documentIndex;
            }

            const match = String(input?.name || '').match(/documents\[(\d+)]/);
            return match ? match[1] : null;
        };
        const draftFileNamesForInput = (input) => {
            const index = documentIndexFromReviewInput(input);
            if (index === null) return [];

            try {
                const storageKey = `non_teaching_document_drafts:${window.location.pathname}:${positionInput?.value ?? 'unknown'}`;
                const drafts = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
                return (drafts[index] || []).map((file) => file.filename).filter(Boolean);
            } catch (_) {
                return [];
            }
        };
        const selectedFileNames = (input) => {
            const files = Array.from(input?.files || []);
            if (files.length) return files.map((file) => file.name);

            return draftFileNamesForInput(input);
        };
        const renderFileNames = (targetId, input) => {
            const target = document.getElementById(targetId);
            const names = selectedFileNames(input);
            target.textContent = '';

            if (!names.length) {
                target.textContent = 'None';
                return;
            }

            if (names.length === 1) {
                target.textContent = names[0];
                return;
            }

            const list = document.createElement('ul');
            list.className = 'review-file-list';
            names.forEach((name) => {
                const line = document.createElement('li');
                line.textContent = name;
                list.appendChild(line);
            });
            target.appendChild(list);
        };

        renderFileNames('review-resume-file', resumeInput);
        renderFileNames('review-cover-file', coverInput);
        renderFileNames('review-certs-file', certsInput);
        renderFileNames('tor', torInput);
        renderFileNames('dip', diplomaInput);
        renderFileNames('prc', boardRatingInput);
        renderFileNames('passing', certificateEligibilityInput);
        renderFileNames('membership', membershipInput);


        transitionForms(documentsForm, reviewForm, 'forward');
        certifyCheckbox.checked = false;
        submitButton.disabled = true;
        setStep(5);
    });

    // Step 4 → Step 3
    btnBackToDocumentsFromReview.addEventListener('click', () => {
        transitionForms(reviewForm, documentsForm, 'back');
        setStep(4);
    });

    const bachelorDegreesContainer = document.getElementById('bachelor-degrees-container');
    const addBachelorDegreeBtn = document.getElementById('addBachelorDegreeBtn');
    const masterDegreesContainer = document.getElementById('master-degrees-container');
    const addMasterDegreeBtn = document.getElementById('addMasterDegreeBtn');
    const doctoralDegreesContainer = document.getElementById('doctoral-degrees-container');
    const addDoctoralDegreeBtn = document.getElementById('addDoctoralDegreeBtn');
    const yearRevealDelayMs = 140;
    const freshGraduateCheckbox = document.getElementById('fresh_graduate');
    const workPositionInput = document.getElementById('work_position');
    const workEmployerInput = document.getElementById('work_employer');
    const workLocationInput = document.getElementById('work_location');
    const workDurationInput = document.getElementById('work_duration');
    const experienceYearsInput = document.getElementById('experience_years');
    const addSkillFieldBtn = document.getElementById('addSkillFieldBtn');
    const keySkillsValue = document.getElementById('key_skills');
    const skillDraftInput = document.getElementById('key_skill_input');
    const skillChipList = document.getElementById('skillChipList');
    const jobSkillRequirements = @json((string) ($openPosition->skills ?? ''));
    let selectedSkills = [];

    function parseSkillList(value = '') {
        return String(value || '')
            .split(/[\n,;|]+/)
            .map((skill) => skill.replace(/^[\s\-*•]+/, '').trim())
            .filter(Boolean);
    }

    function uniqueSkills(skills = []) {
        const seen = new Set();

        return skills.filter((skill) => {
            const key = skill.toLowerCase();
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    }

    function syncKeySkillsValue() {
        if (!keySkillsValue) return;

        const draftSkill = skillDraftInput?.value.trim();
        const skills = [...selectedSkills];
        if (draftSkill && !skills.some((skill) => skill.toLowerCase() === draftSkill.toLowerCase())) {
            skills.push(draftSkill);
        }

        keySkillsValue.value = skills.join(', ');
        keySkillsValue.dispatchEvent(new Event('input', { bubbles: true }));
        if (skillDraftInput) {
            skillDraftInput.required = selectedSkills.length === 0;
        }
    }

    function renderSkillChips() {
        if (!skillChipList) return;

        skillChipList.textContent = '';
        selectedSkills.forEach((skill, index) => {
            const chip = document.createElement('span');
            chip.className = 'skill-chip';
            chip.textContent = skill;

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.setAttribute('aria-label', `Remove ${skill}`);
            removeButton.innerHTML = '<i class="bi bi-x"></i>';
            removeButton.addEventListener('click', () => {
                selectedSkills.splice(index, 1);
                renderSkillChips();
                syncKeySkillsValue();
                skillDraftInput?.focus();
            });

            chip.appendChild(removeButton);
            skillChipList.appendChild(chip);
        });
    }

    function addSkillChip(value = '') {
        const skill = value.trim();
        if (!skill) {
            skillDraftInput?.focus();
            return;
        }

        if (!selectedSkills.some((item) => item.toLowerCase() === skill.toLowerCase())) {
            selectedSkills.push(skill);
        }

        if (skillDraftInput) {
            skillDraftInput.value = '';
            clearErrorHighlight(skillDraftInput);
        }

        renderSkillChips();
        syncKeySkillsValue();
        skillDraftInput?.focus();
    }

    function hydrateSkillFieldsFromValue() {
        if (!keySkillsValue) return;

        selectedSkills = uniqueSkills(parseSkillList(keySkillsValue.value || jobSkillRequirements));

        renderSkillChips();
        syncKeySkillsValue();
    }

    if (skillDraftInput && addSkillFieldBtn && keySkillsValue) {
        skillDraftInput.addEventListener('input', () => {
            clearErrorHighlight(skillDraftInput);
            syncKeySkillsValue();
        });
        skillDraftInput.addEventListener('change', syncKeySkillsValue);
        skillDraftInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addSkillChip(skillDraftInput.value);
            }
        });
        addSkillFieldBtn.addEventListener('click', () => {
            addSkillChip(skillDraftInput.value);
        });
    }

    const updateSingleSelectColor = (select) => {
        if (!select) return;
        if (select.value) {
            select.classList.remove('text-secondary');
            select.classList.add('text-dark');
        } else {
            select.classList.remove('text-dark');
            select.classList.add('text-secondary');
        }
    };

    const toggleBachelorEntryFields = (entry, shouldResetWhenEmpty = true) => {
        if (!entry) return;
        const degreeSelect = entry.querySelector('.bachelor-degree-select');
        const schoolWrapper = entry.querySelector('.bachelor-school-wrapper');
        const schoolInput = entry.querySelector('.bachelor-school-input');
        const yearWrapper = entry.querySelector('.bachelor-year-wrapper');
        const yearInput = entry.querySelector('.bachelor-year-select');
        if (!degreeSelect || !schoolWrapper || !schoolInput || !yearWrapper || !yearInput) return;

        updateSingleSelectColor(degreeSelect);
        if (degreeSelect.value) {
            setTimeout(() => schoolWrapper.classList.remove('year-hidden'), yearRevealDelayMs);
            setTimeout(() => yearWrapper.classList.remove('year-hidden'), yearRevealDelayMs);
        } else {
            schoolWrapper.classList.add('year-hidden');
            yearWrapper.classList.add('year-hidden');
            if (shouldResetWhenEmpty) {
                schoolInput.value = '';
                yearInput.value = '';
            }
        }
    };

    const reindexBachelorEntries = () => {
        const entries = Array.from(bachelorDegreesContainer?.querySelectorAll('.bachelor-degree-entry') || []);
        entries.forEach((entry, index) => {
            entry.dataset.degreeIndex = index;

            const degreeSelect = entry.querySelector('.bachelor-degree-select');
            const schoolInput = entry.querySelector('.bachelor-school-input');
            const yearSelect = entry.querySelector('.bachelor-year-select');
            const degreeLabel = entry.querySelector('label[for^="bachelor_degree_"]');
            const schoolLabel = entry.querySelector('label[for^="bachelor_school_name_"]');
            const yearLabel = entry.querySelector('label[for^="bachelor_year_finished_"]');
            const removeBtn = entry.querySelector('.remove-bachelor-degree');

            if (degreeSelect) {
                degreeSelect.id = `bachelor_degree_${index}`;
                degreeSelect.name = `bachelor_degrees[${index}][degree]`;
            }
            if (schoolInput) {
                schoolInput.id = `bachelor_school_name_${index}`;
                schoolInput.name = `bachelor_degrees[${index}][school_name]`;
            }
            if (yearSelect) {
                yearSelect.id = `bachelor_year_finished_${index}`;
                yearSelect.name = `bachelor_degrees[${index}][year_finished]`;
            }
            if (degreeLabel) degreeLabel.setAttribute('for', `bachelor_degree_${index}`);
            if (schoolLabel) schoolLabel.setAttribute('for', `bachelor_school_name_${index}`);
            if (yearLabel) yearLabel.setAttribute('for', `bachelor_year_finished_${index}`);
            if (removeBtn) removeBtn.classList.toggle('d-none', index === 0 && entries.length === 1);
        });
    };

    const bindBachelorEntry = (entry, shouldResetWhenEmpty = true) => {
        if (!entry) return;
        const degreeSelect = entry.querySelector('.bachelor-degree-select');
        const removeBtn = entry.querySelector('.remove-bachelor-degree');

        degreeSelect?.addEventListener('change', () => toggleBachelorEntryFields(entry, true));
        degreeSelect?.addEventListener('input', () => toggleBachelorEntryFields(entry, false));
        removeBtn?.addEventListener('click', () => {
            const entries = bachelorDegreesContainer?.querySelectorAll('.bachelor-degree-entry') || [];
            if (entries.length <= 1) return;
            entry.remove();
            reindexBachelorEntries();
        });

        toggleBachelorEntryFields(entry, shouldResetWhenEmpty);
    };

    const addBachelorDegreeEntry = () => {
        const firstEntry = bachelorDegreesContainer?.querySelector('.bachelor-degree-entry');
        if (!firstEntry || !bachelorDegreesContainer) return;

        const clone = firstEntry.cloneNode(true);
        clone.querySelectorAll('input, select').forEach((field) => {
            if ((field.type || '').toLowerCase() === 'select-one') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
        });
        clone.querySelectorAll('.year-field-transition').forEach((wrapper) => wrapper.classList.add('year-hidden'));

        bachelorDegreesContainer.appendChild(clone);
        reindexBachelorEntries();
        bindBachelorEntry(clone, true);
    };

    addBachelorDegreeBtn?.addEventListener('click', addBachelorDegreeEntry);
    Array.from(bachelorDegreesContainer?.querySelectorAll('.bachelor-degree-entry') || []).forEach((entry) => {
        bindBachelorEntry(entry, false);
    });
    reindexBachelorEntries();

    const toggleMasterEntryFields = (entry, shouldResetWhenEmpty = true) => {
        if (!entry) return;
        const degreeSelect = entry.querySelector('.master-degree-select');
        const schoolWrapper = entry.querySelector('.master-school-wrapper');
        const schoolInput = entry.querySelector('.master-school-input');
        const yearWrapper = entry.querySelector('.master-year-wrapper');
        const yearInput = entry.querySelector('.master-year-select');
        if (!degreeSelect || !schoolWrapper || !schoolInput || !yearWrapper || !yearInput) return;

        updateSingleSelectColor(degreeSelect);
        if (degreeSelect.value) {
            setTimeout(() => schoolWrapper.classList.remove('year-hidden'), yearRevealDelayMs);
            setTimeout(() => yearWrapper.classList.remove('year-hidden'), yearRevealDelayMs);
        } else {
            schoolWrapper.classList.add('year-hidden');
            yearWrapper.classList.add('year-hidden');
            if (shouldResetWhenEmpty) {
                schoolInput.value = '';
                yearInput.value = '';
            }
        }
    };

    const reindexMasterEntries = () => {
        const entries = Array.from(masterDegreesContainer?.querySelectorAll('.master-degree-entry') || []);
        entries.forEach((entry, index) => {
            entry.dataset.degreeIndex = index;

            const degreeSelect = entry.querySelector('.master-degree-select');
            const schoolInput = entry.querySelector('.master-school-input');
            const yearSelect = entry.querySelector('.master-year-select');
            const degreeLabel = entry.querySelector('label[for^="master_degree_"]');
            const schoolLabel = entry.querySelector('label[for^="master_school_name_"]');
            const yearLabel = entry.querySelector('label[for^="master_year_finished_"]');
            const removeBtn = entry.querySelector('.remove-master-degree');

            if (degreeSelect) {
                degreeSelect.id = `master_degree_${index}`;
                degreeSelect.name = `master_degrees[${index}][degree]`;
            }
            if (schoolInput) {
                schoolInput.id = `master_school_name_${index}`;
                schoolInput.name = `master_degrees[${index}][school_name]`;
            }
            if (yearSelect) {
                yearSelect.id = `master_year_finished_${index}`;
                yearSelect.name = `master_degrees[${index}][year_finished]`;
            }
            if (degreeLabel) degreeLabel.setAttribute('for', `master_degree_${index}`);
            if (schoolLabel) schoolLabel.setAttribute('for', `master_school_name_${index}`);
            if (yearLabel) yearLabel.setAttribute('for', `master_year_finished_${index}`);
            if (removeBtn) removeBtn.classList.toggle('d-none', index === 0 && entries.length === 1);
        });
    };

    const bindMasterEntry = (entry, shouldResetWhenEmpty = true) => {
        if (!entry) return;
        const degreeSelect = entry.querySelector('.master-degree-select');
        const removeBtn = entry.querySelector('.remove-master-degree');

        degreeSelect?.addEventListener('change', () => toggleMasterEntryFields(entry, true));
        degreeSelect?.addEventListener('input', () => toggleMasterEntryFields(entry, false));
        removeBtn?.addEventListener('click', () => {
            const entries = masterDegreesContainer?.querySelectorAll('.master-degree-entry') || [];
            if (entries.length <= 1) return;
            entry.remove();
            reindexMasterEntries();
        });

        toggleMasterEntryFields(entry, shouldResetWhenEmpty);
    };

    const addMasterDegreeEntry = () => {
        const firstEntry = masterDegreesContainer?.querySelector('.master-degree-entry');
        if (!firstEntry || !masterDegreesContainer) return;

        const clone = firstEntry.cloneNode(true);
        clone.querySelectorAll('input, select').forEach((field) => {
            if ((field.type || '').toLowerCase() === 'select-one') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
        });
        clone.querySelectorAll('.year-field-transition').forEach((wrapper) => wrapper.classList.add('year-hidden'));

        masterDegreesContainer.appendChild(clone);
        reindexMasterEntries();
        bindMasterEntry(clone, true);
    };

    addMasterDegreeBtn?.addEventListener('click', addMasterDegreeEntry);
    Array.from(masterDegreesContainer?.querySelectorAll('.master-degree-entry') || []).forEach((entry) => {
        bindMasterEntry(entry, false);
    });
    reindexMasterEntries();

    const toggleDoctoralEntryFields = (entry, shouldResetWhenEmpty = true) => {
        if (!entry) return;
        const degreeSelect = entry.querySelector('.doctoral-degree-select');
        const schoolWrapper = entry.querySelector('.doctoral-school-wrapper');
        const schoolInput = entry.querySelector('.doctoral-school-input');
        const yearWrapper = entry.querySelector('.doctoral-year-wrapper');
        const yearInput = entry.querySelector('.doctoral-year-select');
        if (!degreeSelect || !schoolWrapper || !schoolInput || !yearWrapper || !yearInput) return;

        updateSingleSelectColor(degreeSelect);
        if (degreeSelect.value) {
            setTimeout(() => schoolWrapper.classList.remove('year-hidden'), yearRevealDelayMs);
            setTimeout(() => yearWrapper.classList.remove('year-hidden'), yearRevealDelayMs);
        } else {
            schoolWrapper.classList.add('year-hidden');
            yearWrapper.classList.add('year-hidden');
            if (shouldResetWhenEmpty) {
                schoolInput.value = '';
                yearInput.value = '';
            }
        }
    };

    const reindexDoctoralEntries = () => {
        const entries = Array.from(doctoralDegreesContainer?.querySelectorAll('.doctoral-degree-entry') || []);
        entries.forEach((entry, index) => {
            entry.dataset.degreeIndex = index;

            const degreeSelect = entry.querySelector('.doctoral-degree-select');
            const schoolInput = entry.querySelector('.doctoral-school-input');
            const yearSelect = entry.querySelector('.doctoral-year-select');
            const degreeLabel = entry.querySelector('label[for^="doctoral_degree_"]');
            const schoolLabel = entry.querySelector('label[for^="doctoral_school_name_"]');
            const yearLabel = entry.querySelector('label[for^="doctoral_year_finished_"]');
            const removeBtn = entry.querySelector('.remove-doctoral-degree');

            if (degreeSelect) {
                degreeSelect.id = `doctoral_degree_${index}`;
                degreeSelect.name = `doctoral_degrees[${index}][degree]`;
            }
            if (schoolInput) {
                schoolInput.id = `doctoral_school_name_${index}`;
                schoolInput.name = `doctoral_degrees[${index}][school_name]`;
            }
            if (yearSelect) {
                yearSelect.id = `doctoral_year_finished_${index}`;
                yearSelect.name = `doctoral_degrees[${index}][year_finished]`;
            }
            if (degreeLabel) degreeLabel.setAttribute('for', `doctoral_degree_${index}`);
            if (schoolLabel) schoolLabel.setAttribute('for', `doctoral_school_name_${index}`);
            if (yearLabel) yearLabel.setAttribute('for', `doctoral_year_finished_${index}`);
            if (removeBtn) removeBtn.classList.toggle('d-none', index === 0 && entries.length === 1);
        });
    };

    const bindDoctoralEntry = (entry, shouldResetWhenEmpty = true) => {
        if (!entry) return;
        const degreeSelect = entry.querySelector('.doctoral-degree-select');
        const removeBtn = entry.querySelector('.remove-doctoral-degree');

        degreeSelect?.addEventListener('change', () => toggleDoctoralEntryFields(entry, true));
        degreeSelect?.addEventListener('input', () => toggleDoctoralEntryFields(entry, false));
        removeBtn?.addEventListener('click', () => {
            const entries = doctoralDegreesContainer?.querySelectorAll('.doctoral-degree-entry') || [];
            if (entries.length <= 1) return;
            entry.remove();
            reindexDoctoralEntries();
        });

        toggleDoctoralEntryFields(entry, shouldResetWhenEmpty);
    };

    const addDoctoralDegreeEntry = () => {
        const firstEntry = doctoralDegreesContainer?.querySelector('.doctoral-degree-entry');
        if (!firstEntry || !doctoralDegreesContainer) return;

        const clone = firstEntry.cloneNode(true);
        clone.querySelectorAll('input, select').forEach((field) => {
            if ((field.type || '').toLowerCase() === 'select-one') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
        });
        clone.querySelectorAll('.year-field-transition').forEach((wrapper) => wrapper.classList.add('year-hidden'));

        doctoralDegreesContainer.appendChild(clone);
        reindexDoctoralEntries();
        bindDoctoralEntry(clone, true);
    };

    addDoctoralDegreeBtn?.addEventListener('click', addDoctoralDegreeEntry);
    Array.from(doctoralDegreesContainer?.querySelectorAll('.doctoral-degree-entry') || []).forEach((entry) => {
        bindDoctoralEntry(entry, false);
    });
    reindexDoctoralEntries();

    const toggleFreshGraduateFields = () => {
        if (
            !freshGraduateCheckbox
            || !workPositionInput
            || !workEmployerInput
            || !workLocationInput
            || !workDurationInput
            || !experienceYearsInput
        ) {
            return;
        }

        const isFreshGraduate = freshGraduateCheckbox.checked;
        const workFields = [workPositionInput, workEmployerInput, workLocationInput, workDurationInput];

        workFields.forEach((field) => {
            field.disabled = isFreshGraduate;
            if (isFreshGraduate) {
                field.value = '';
                clearErrorHighlight(field);
            }
        });

        if (isFreshGraduate) {
            const zeroToOneOption = Array.from(experienceYearsInput.options).find((option) => option.value.startsWith('0'));
            if (zeroToOneOption) {
                experienceYearsInput.value = zeroToOneOption.value;
            }
            // Keep it submittable while preventing edits for fresh graduates.
            experienceYearsInput.style.pointerEvents = 'none';
            experienceYearsInput.tabIndex = -1;
            experienceYearsInput.setAttribute('aria-disabled', 'true');
            experienceYearsInput.classList.remove('text-secondary');
            experienceYearsInput.classList.add('text-dark');
            clearErrorHighlight(experienceYearsInput);
        } else {
            experienceYearsInput.style.pointerEvents = '';
            experienceYearsInput.tabIndex = 0;
            experienceYearsInput.removeAttribute('aria-disabled');
            if (!experienceYearsInput.value) {
                experienceYearsInput.classList.remove('text-dark');
                experienceYearsInput.classList.add('text-secondary');
            }
        }
    };

    freshGraduateCheckbox?.addEventListener('change', toggleFreshGraduateFields);
    hydrateSkillFieldsFromValue();
    toggleFreshGraduateFields();

});

</script>








@endsection
