@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .site-footer {
        background:
            radial-gradient(circle at top left, rgba(21, 115, 71, 0.12), transparent 24%),
            linear-gradient(180deg, #0f1113 0%, #0b0c0d 100%);
        color: rgba(255, 255, 255, 0.82);
        margin-top: 4rem;
    }

    .site-footer a {
        color: rgba(255, 255, 255, 0.82);
        text-decoration: none;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    .site-footer a:hover {
        color: #ffffff;
        transform: translateX(2px);
    }

    .footer-shell {
        max-width: 1240px;
        margin: 0 auto;
        padding: 4rem 1.5rem 2rem;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1.3fr 1fr 1fr;
        gap: 2rem;
    }

    .footer-brand {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .footer-brand-mark {
        width: 3.75rem;
        height: 3.75rem;
        border-radius: 50%;
        object-fit: cover;
        background: #fff;
        padding: 0.35rem;
        box-shadow: 0 14px 26px rgba(0, 0, 0, 0.28);
    }

    .footer-brand h3,
    .footer-title {
        margin: 0 0 1.25rem;
        color: #fff;
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .footer-title {
        font-size: 1.15rem;
    }

    .footer-info-list,
    .footer-link-list {
        list-style: none;
        padding: 0;
        margin: 1.4rem 0 0;
    }

    .footer-info-list li,
    .footer-link-list li {
        margin-bottom: 0.95rem;
    }

    .footer-contact {
        display: flex;
        align-items: flex-start;
        gap: 0.8rem;
    }

    .footer-icon {
        width: 1.2rem;
        height: 1.2rem;
        flex: 0 0 1.2rem;
        color: rgba(255, 255, 255, 0.85);
        margin-top: 0.15rem;
    }

    .footer-feature-text {
        margin: 0;
        max-width: 18rem;
        color: rgba(255, 255, 255, 0.78);
        line-height: 1.9;
    }

    .newsletter-copy {
        margin: 0 0 1rem;
        color: rgba(255, 255, 255, 0.72);
        line-height: 1.7;
    }

    .newsletter-form {
        display: grid;
        gap: 0.8rem;
    }

    .newsletter-input-wrap {
        position: relative;
    }

    .newsletter-input {
        width: 100%;
        height: 3rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
        padding: 0.8rem 3rem 0.8rem 1rem;
        outline: none;
    }

    .newsletter-input::placeholder {
        color: rgba(255, 255, 255, 0.45);
    }

    .newsletter-input:focus {
        border-color: rgba(52, 211, 153, 0.55);
        box-shadow: 0 0 0 0.18rem rgba(52, 211, 153, 0.15);
    }

    .newsletter-input-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.1rem;
        height: 1.1rem;
        color: rgba(255, 255, 255, 0.55);
    }

    .newsletter-btn {
        border: none;
        border-radius: 0.95rem;
        min-height: 3rem;
        background: linear-gradient(135deg, #1b1d1f 0%, #232628 100%);
        color: #fff;
        font-weight: 700;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
    }

    .newsletter-btn:hover {
        background: linear-gradient(135deg, #157347 0%, #1ea55d 100%);
    }

    .newsletter-note {
        margin: 0.35rem 0 0;
        color: rgba(191, 219, 254, 0.8);
        font-size: 0.9rem;
    }

    .footer-bottom {
        margin-top: 3rem;
        padding-top: 1.4rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .footer-bottom p {
        margin: 0;
        color: rgba(255, 255, 255, 0.68);
    }

    .footer-bottom-links {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    @media (max-width: 991.98px) {
        .footer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .footer-shell {
            padding: 3rem 1rem 1.5rem;
        }

        .footer-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .footer-bottom {
            flex-direction: column;
            align-items: flex-start;
        }

        .footer-bottom-links {
            gap: 1rem;
        }
    }

    .applications-shell {
        max-width: 1240px;
    }

    .applications-board {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.35rem;
        background: #fff;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
        padding: 1.4rem;
    }

    .applications-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 1.35rem;
        padding: 0.35rem 0.25rem 0;
    }

    .applications-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: #047857;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 0.45rem;
    }

    .applications-title {
        margin: 0;
        color: #1f2937;
        font-size: clamp(1.8rem, 4vw, 2.45rem);
        font-weight: 900;
        letter-spacing: 0;
    }

    .applications-subtitle {
        margin: 0.35rem 0 0;
        color: #64748b;
        font-weight: 600;
    }

    .applications-count-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        white-space: nowrap;
        border: 1px solid rgba(16, 185, 129, 0.28);
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        padding: 0.65rem 0.9rem;
        font-size: 0.88rem;
        font-weight: 800;
    }

    .application-card {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 1.25rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .application-card + .application-card {
        margin-top: 1rem;
    }

    .application-card-body {
        padding: 1.15rem;
    }

    .application-card-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: start;
        margin-bottom: 1rem;
    }

    .application-position {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 0.3rem;
    }

    .application-position h5 {
        margin: 0;
        color: #111827;
        font-size: 1.25rem;
        font-weight: 900;
    }

    .application-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 700;
    }

    .application-status-badge {
        border-radius: 999px;
        padding: 0.55rem 0.9rem;
        font-size: 0.78rem;
        font-weight: 900;
        text-transform: capitalize;
    }

    .application-flow-strip {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #fff;
        padding: 0.9rem;
        margin-bottom: 1rem;
    }

    .stepper {
        display: flex;
        align-items: center;
        max-width: 36rem;
        gap: 0;
        margin-bottom: 0.75rem;
    }

    .stepper .step {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
    }

    .stepper .step:last-child {
        flex: 0 0 auto;
    }

    .stepper .circle {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e5e7eb;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 900;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .stepper .line {
        height: 0.22rem;
        flex: 1;
        margin: 0 0.45rem;
        border-radius: 999px;
        background: #e5e7eb;
    }

    .application-flow-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .next-step-text {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: #047857;
        font-weight: 800;
    }

    .next-step-text::before {
        content: "";
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 50%;
        background: currentColor;
    }

    .application-view-toggle {
        border-radius: 999px;
        padding: 0.55rem 0.9rem;
        font-weight: 800;
    }

    .application-details-shell {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.15rem;
        background: #fff;
        padding: 1rem;
    }

    .application-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .application-section-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #f8fafc;
        padding: 1rem;
    }

    .application-section-panel-wide {
        grid-column: 1 / -1;
    }

    .application-section-title {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0 0 0.9rem;
        color: #047857;
        font-size: 0.82rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .application-section-panel > .small {
        color: #64748b !important;
        font-size: 0.75rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .application-section-panel > .small + div {
        color: #1f2937;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .detail-list {
        display: grid;
        gap: 0.7rem;
    }

    .detail-row {
        display: grid;
        grid-template-columns: minmax(7rem, 0.35fr) minmax(0, 1fr);
        gap: 0.75rem;
        align-items: start;
        border-top: 1px solid rgba(15, 23, 42, 0.06);
        padding-top: 0.7rem;
    }

    .detail-row:first-child {
        border-top: 0;
        padding-top: 0;
    }

    .detail-label {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .detail-value {
        color: #1f2937;
        font-size: 0.94rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .education-card-list,
    .document-card-grid {
        display: grid;
        gap: 0.75rem;
    }

    .education-card-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .document-card-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .mini-record-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.9rem;
        background: #fff;
        padding: 0.9rem;
        min-height: 5.25rem;
    }

    .mini-record-title {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.35rem;
        color: #111827;
        font-weight: 900;
    }

    .mini-record-title i {
        color: #047857;
    }

    .mini-record-main {
        color: #1f2937;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .mini-record-sub {
        margin-top: 0.2rem;
        color: #64748b;
        font-size: 0.84rem;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .application-tips {
        border: 1px solid rgba(16, 185, 129, 0.22);
        background: #ecfdf5;
    }

    @media (max-width: 991.98px) {
        .application-detail-grid,
        .education-card-list,
        .document-card-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .applications-board {
            padding: 1rem;
        }

        .applications-header,
        .application-card-top,
        .application-flow-footer,
        .detail-row {
            grid-template-columns: 1fr;
        }

        .applications-header,
        .application-flow-footer {
            align-items: stretch;
        }
    }
</style>

@include('layouts.header')  {{-- UNIVERSAL HEADER --}}


<div class="header-divider"></div>
<main class="container my-5 animated-card1 delay-5 applications-shell">
    <div
        id="applicationStatusBoard"
        class="applications-board"
        data-lookup="{{ e($searchedEmail ?? '') }}"
        data-signature="{{ e($applicationStatusSignature ?? '') }}"
        data-refresh-url="{{ route('guest.application.check') }}"
    >
    <div class="applications-header">
        <div>
            <div class="applications-kicker"><i class="bi bi-clipboard-check"></i> Application Status</div>
            <h2 class="applications-title">Your Applications</h2>
            <p class="applications-subtitle">Track each submitted application and review the details you sent to HR.</p>
        </div>
        <div class="applications-count-pill">
            <i class="bi bi-folder2-open"></i>
            {{ ($applicants ?? collect())->count() }} {{ ($applicants ?? collect())->count() === 1 ? 'Application' : 'Applications' }}
        </div>
    </div>

    @if(($applicants ?? collect())->isEmpty())
        <div class="rounded-4 border bg-light-subtle p-4 text-center">
            <h5 class="fw-bold mb-2">No application found</h5>
            @if(!empty($searchedEmail))
                <p class="text-muted mb-0">We could not find an application with tracking number {{ $searchedEmail }}.</p>
            @else
                <p class="text-muted mb-0">Enter your tracking number from the Application Status button to view your submitted application.</p>
            @endif
        </div>
    @endif

    @foreach($applicants as $applicant)
        <div class="application-card animated-card delay-5">
            <div class="application-card-body card-body">

                <div class="application-card-top">
                    <div>
                        <div class="application-position">
                            <h5 class="mb-1">{{ optional($applicant->position)->title ?: 'Application' }}</h5>
                            @if((bool) ($applicant->is_email_history_match ?? false))
                                <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(108, 117, 125, 0.12); color: #495057; border: 1px solid rgba(108, 117, 125, 0.35);">
                                    Previous Application
                                </span>
                            @endif
                        </div>
                        <div class="application-meta">
                            <span><i class="bi bi-calendar3 me-1"></i>Submitted {{ $applicant->created_at->format('M d, Y') }}</span>
                            <span><i class="bi bi-building me-1"></i>{{ optional($applicant->position)->department ?: 'Department N/A' }}</span>
                            @if(!empty($applicant->tracking_number))
                                <span><i class="bi bi-upc-scan me-1"></i>{{ $applicant->tracking_number }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Status Badge --}}  
                    @php
                        $statusColors = [
                            'pending' => 'background-color: rgba(255, 193, 7, 0.3); color: #ffa807; border: 2px solid #ffc107;',
                            'Hired' => 'background-color: rgba(25, 135, 84, 0.2); color: #198754; border: 2px solid #198754;',
                            'Completed' => 'background-color: rgba(25, 135, 84, 0.2); color: #198754; border: 2px solid #198754;',
                            'Rejected' => 'background-color: rgba(220, 53, 69, 0.2); color: #dc3545; border: 2px solid #dc3545;',
                            'Under Review' => 'background-color: rgba(13, 110, 253, 0.2); color: #0d6efd; border: 2px solid #0d6efd;',
                            'Demo Teaching' => 'background-color: rgba(13, 110, 253, 0.2); color: #0d6efd; border: 2px solid #0d6efd;',
                        ];
                        $defaultColor = 'background-color: rgba(13, 110, 253, 0.2); color: #0d6efd; border: 2px solid #0d6efd;';
                        $badgeStyle = $statusColors[$applicant->application_status] ?? $defaultColor;
                    @endphp

                    <span class="application-status-badge" style="{{ $badgeStyle }}">
                        {{ $applicant->application_status }}
                    </span>
                </div>

                {{-- Progress --}}
                <div class="application-flow-strip">
                    <div
                        class="stepper"
                        data-status="{{ $applicant->application_status }}"
                        data-job-type="{{ strtolower((string) optional($applicant->position)->job_type) }}"
                    ></div>

                    <div class="application-flow-footer">
                        <span class="next-step-text"></span>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success application-view-toggle"
                            data-target="application-details-{{ $applicant->id }}"
                        >
                            View Submitted Application
                        </button>
                    </div>
                </div>

                {{-- Rejection Message --}}
                @if($applicant->application_status === 'Rejected')
                    <div class="alert alert-danger mt-3">
                        <p>Thank you very much for your interest in the <strong>{{ optional($applicant->position)->title ?: 'selected' }}</strong> position and for the time you invested in the application process.</p>

                        <p>After careful consideration, we regret to inform you that we will not be moving forward with your application at this time. While your qualifications are impressive, we have chosen to proceed with candidates whose experience more closely matches the requirements of this role.</p>

                        <p>We truly appreciate your interest in joining our team and encourage you to apply for future openings that align with your skills and experience.</p>
                    </div>
                @endif

                <div id="application-details-{{ $applicant->id }}" class="application-details-panel mt-3" style="display: none;">
                    <div class="application-details-shell">
                        <div class="application-detail-grid">
                            <div class="application-section-panel">
                                <h6 class="application-section-title"><i class="bi bi-person-badge"></i> Personal Information</h6>
                                <div class="small text-muted mb-2">Full Name</div>
                                <div class="mb-3">{{ $applicant->first_name }} {{ $applicant->last_name }}</div>
                                <div class="small text-muted mb-2">Email</div>
                                <div class="mb-3">{{ $applicant->email }}</div>
                                <div class="small text-muted mb-2">Phone</div>
                                <div class="mb-3">{{ $applicant->phone ?: 'N/A' }}</div>
                                <div class="small text-muted mb-2">Address</div>
                                <div>{{ $applicant->address ?: 'N/A' }}</div>
                            </div>

                            <div class="application-section-panel">
                                <h6 class="application-section-title"><i class="bi bi-briefcase"></i> Application Information</h6>
                                <div class="small text-muted mb-2">Position</div>
                                <div class="mb-3">{{ optional($applicant->position)->title ?: 'N/A' }}</div>
                                <div class="small text-muted mb-2">Department</div>
                                <div class="mb-3">{{ optional($applicant->position)->department ?: 'N/A' }}</div>
                                <div class="small text-muted mb-2">Experience Years</div>
                                <div class="mb-3">{{ $applicant->experience_years ?: 'N/A' }}</div>
                                <div class="small text-muted mb-2">Skills / Expertise</div>
                                <div>{{ $applicant->skills_n_expertise ?: 'N/A' }}</div>
                            </div>

                            <div class="application-section-panel">
                                <h6 class="application-section-title"><i class="bi bi-mortarboard"></i> Education</h6>
                                @php
                                    $degreeRows = collect($applicant->degrees ?? []);
                                @endphp
                                @if($degreeRows->isNotEmpty())
                                    <div class="education-card-list">
                                        @foreach($degreeRows as $degree)
                                            <div class="mini-record-card">
                                                <div class="mini-record-title"><i class="bi bi-award"></i><span class="text-capitalize">{{ $degree->degree_level }}</span></div>
                                                <div class="mini-record-main">{{ $degree->degree_name ?: 'N/A' }}</div>
                                                <div class="small text-muted">{{ $degree->school_name ?: 'School N/A' }}{{ $degree->year_finished ? ' • '.$degree->year_finished : '' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mini-record-card">
                                        <div><strong>Bachelor:</strong> {{ $applicant->bachelor_degree ?: 'N/A' }}</div>
                                        <div><strong>Master:</strong> {{ $applicant->master_degree ?: 'N/A' }}</div>
                                        <div><strong>Doctorate:</strong> {{ $applicant->doctoral_degree ?: 'N/A' }}</div>
                                    </div>
                                @endif
                            </div>

                            <div class="application-section-panel">
                                <h6 class="application-section-title"><i class="bi bi-clock-history"></i> Experience</h6>
                                <div class="small text-muted mb-2">Previous Position</div>
                                <div class="mb-3">{{ $applicant->work_position ?: 'N/A' }}</div>
                                <div class="small text-muted mb-2">Employer</div>
                                <div class="mb-3">{{ $applicant->work_employer ?: 'N/A' }}</div>
                                <div class="small text-muted mb-2">Work Location</div>
                                <div class="mb-3">{{ $applicant->work_location ?: 'N/A' }}</div>
                                <div class="small text-muted mb-2">Work Duration</div>
                                <div>{{ $applicant->work_duration ?: 'N/A' }}</div>
                            </div>

                            <div class="application-section-panel application-section-panel-wide">
                                <h6 class="application-section-title"><i class="bi bi-folder-check"></i> Uploaded Documents</h6>
                                @if(collect($applicant->documents ?? [])->isNotEmpty())
                                    <div class="document-card-grid">
                                        @foreach($applicant->documents as $document)
                                            <div class="mini-record-card">
                                                <div class="mini-record-title"><i class="bi bi-file-earmark-text"></i>{{ $document->type ?: 'Document' }}</div>
                                                <div class="mini-record-sub">{{ $document->filename }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mini-record-card">No uploaded documents found.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    </div>

        <div class="container my-5 shadow-sm p-4 rounded application-tips">
            <div class="d-flex align-items-start">
                <!-- Icon -->
                <div class="me-3">
                    <i class="bi bi-lightbulb-fill tips-icon"></i>
                </div>

                <!-- Content -->
                <div>
                    <h6 class="fw-bold mb-2">Application Tips</h6>
                    <ul class="list-unstyled mb-0">
                        <li>• Check your email regularly for updates from our HR team</li>
                        <li>• You will be notified at each stage of the application process</li>
                        <li>• Interview invitations will be sent via email at least 3 days notice</li>
                    </ul>
                </div>
            </div>
        </div>

</main>

<footer class="site-footer">
    <div class="footer-shell">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="{{ asset('images/nclogo.png') }}" alt="Northeastern College logo" class="footer-brand-mark">
                    <div>
                        <h3>Northeastern<br>College</h3>
                    </div>
                </div>

                <ul class="footer-info-list">
                    <li>
                        <a href="https://www.google.com/maps/search/?api=1&query=Villasis%2C+Santiago+City%2C+Isabela+3311" target="_blank" rel="noopener noreferrer" class="footer-contact">
                            <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z"/>
                                <circle cx="12" cy="10" r="2.5"/>
                            </svg>
                            <span>Villasis, Santiago City<br>Isabela, 3311</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/NCnianAko" target="_blank" rel="noopener noreferrer" class="footer-contact">
                            <svg class="footer-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M13.5 21v-7h2.3l.4-3h-2.7V9.2c0-.9.3-1.5 1.6-1.5H16V5.1c-.3 0-1.2-.1-2.2-.1-2.2 0-3.8 1.3-3.8 3.8V11H7.5v3H10v7h3.5Z"/>
                            </svg>
                            <span>facebook.com/NCnianAko</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://icloudph.com/nc/sias/" target="_blank" rel="noopener noreferrer" class="footer-contact">
                            <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                            </svg>
                            <span>SIAS Online</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-link-list">
                    <li><a href="{{ route('guest.index') }}">Home</a></li>
                    <li><a href="{{ route('guest.jobOpenLanding') }}">Job Vacancies</a></li>
                    <li><a href="{{ route('login_display') }}">Applicant Login</a></li>
                    <li><a href="{{ route('register') }}">Create Account</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-title">About</h4>
                <p class="footer-feature-text">Building careers, growing leaders, and creating meaningful opportunities for the next generation.</p>
            </div>

            
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 Northeastern College. All rights reserved.</p>
            <div class="footer-bottom-links">
                <a href="{{ route('guest.policy') }}">Privacy Policy</a>
                <a href="{{ route('guest.terms') }}">Terms of Service</a>
                <a href="{{ route('guest.cookie') }}">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {

    const getSteps = (jobType) => {
        const normalized = (jobType || '').toLowerCase().trim();
        const isTeaching = normalized.includes('teaching') && !normalized.includes('non');

        if (isTeaching) {
            return [
                'pending',
                'Under Review',
                'Initial Interview',
                'Final Interview',
                'Demo Teaching',
                'Passing Document',
            ];
        }

        return [
            'pending',
            'Under Review',
            'Initial Interview',
            'Final Interview',
            'Passing Document',
        ];
    };

    function initializeApplicationStatusBoard(root = document) {
    root.querySelectorAll('.stepper').forEach(stepper => {
        const status = stepper.dataset.status;
        const steps = getSteps(stepper.dataset.jobType);
        stepper.innerHTML = steps.map((_, index) => `
            <div class="step">
                <div class="circle">${index + 1}</div>
                ${index < steps.length - 1 ? '<div class="line"></div>' : ''}
            </div>
        `).join('');

        const stepElements = stepper.querySelectorAll('.step');
        const nextText = stepper
            .closest('.card-body')
            .querySelector('.next-step-text');

        const currentStep = steps.indexOf(status);

        stepElements.forEach((step, index) => {
            const circle = step.querySelector('.circle');
            const line = step.querySelector('.line');

            // Reset styles
            step.classList.remove('completed', 'rejected');
            circle.style.backgroundColor = '';
            circle.style.color = '';
            if(line) line.style.backgroundColor = '';

            if (status === 'Rejected') {
                // All circles red
                step.classList.add('rejected');
                circle.innerText = index + 1;
                circle.style.backgroundColor = '#dc3545'; // red
                circle.style.color = '#fff';
                
                // All lines red
                if(line) line.style.backgroundColor = '#dc3545';
            } 
            else if (status === 'Hired' || status === 'Completed') {
                step.classList.add('completed');
                circle.innerText = '✓';
                circle.style.backgroundColor = '#198754'; // green
                circle.style.color = '#fff';
                if(line) line.style.backgroundColor = '#198754';
            } 
            else {
                // Other statuses: normal stepper logic
                if (index < currentStep) {
                    step.classList.add('completed');
                    circle.innerText = '✓';
                    circle.style.backgroundColor = '#198754';
                    circle.style.color = '#fff';
                    if(line) line.style.backgroundColor = '#198754';
                } else if (index === currentStep) {
                    step.classList.add('completed');
                    circle.innerText = index + 1;
                } else {
                    circle.innerText = index + 1;
                }
            }
        });

        // Text below stepper
        if (status === 'Hired') {
            nextText.innerText = 'Hired';
        } else if (status === 'Completed') {
            nextText.innerText = 'Process Completed';
        } else if (status === 'Rejected') {
            nextText.innerText = 'Application Rejected';
        } else if (currentStep === steps.length - 1) {
            nextText.innerText = 'Next: Completed';
        } else {
            nextText.innerText =
                currentStep >= 0 && currentStep < steps.length - 1
                    ? `Next: ${steps[currentStep + 1]}`
                    : 'In Progress';
        }
    });

    root.querySelectorAll('.application-view-toggle').forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const panel = document.getElementById(targetId);
            if (!panel) return;

            const isOpen = panel.style.display !== 'none';
            panel.style.display = isOpen ? 'none' : 'block';
            this.innerText = isOpen ? 'View Submitted Application' : 'Hide Submitted Application';
        });
    });
    }

    initializeApplicationStatusBoard();

    const board = document.getElementById('applicationStatusBoard');
    let statusRefreshInFlight = false;

    async function refreshApplicationStatusBoard(force = false) {
        if (!board || statusRefreshInFlight) return;
        if (!force && document.hidden) return;

        const lookup = board.dataset.lookup || '';
        const refreshUrl = board.dataset.refreshUrl || '';
        if (!lookup || !refreshUrl) return;

        statusRefreshInFlight = true;

        try {
            const url = new URL(refreshUrl, window.location.origin);
            url.searchParams.set('application_lookup', lookup);
            if (board.dataset.signature) {
                url.searchParams.set('signature', board.dataset.signature);
            }

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            const payload = await response.json();
            board.dataset.signature = payload.signature || board.dataset.signature || '';

            if (payload.changed === false || typeof payload.html !== 'string') {
                return;
            }

            board.innerHTML = payload.html;
            initializeApplicationStatusBoard(board);
        } catch (error) {
            console.warn('Application status refresh failed.', error);
        } finally {
            statusRefreshInFlight = false;
        }
    }

    setInterval(() => refreshApplicationStatusBoard(), 15000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshApplicationStatusBoard(true);
        }
    });

});

</script>



