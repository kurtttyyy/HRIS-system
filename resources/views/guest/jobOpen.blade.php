@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .job-open-item.hover-card:hover {
        border-color: #22c55e !important;
        box-shadow:
            0 28px 60px rgba(34, 197, 94, 0.45),
            0 0 0 6px rgba(34, 197, 94, 0.30),
            0 0 34px rgba(34, 197, 94, 0.42) !important;
    }

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

    #guest-job-applicant-page .guest-job-reveal {
        opacity: 1;
        transform: translateY(0);
        will-change: opacity, transform;
    }

    #guest-job-applicant-page .guest-job-reveal.is-scroll-animated,
    .site-footer.guest-job-reveal.is-scroll-animated {
        animation: guest-job-fade-up 0.72s cubic-bezier(0.22, 1, 0.36, 1) both;
        animation-delay: var(--guest-job-delay, 0ms);
    }

    #guest-job-applicant-page .guest-job-card-motion,
    .site-footer .guest-job-card-motion {
        transition:
            transform 0.25s ease,
            box-shadow 0.25s ease,
            border-color 0.25s ease;
    }

    #guest-job-applicant-page .guest-job-card-motion:hover,
    .site-footer .guest-job-card-motion:hover {
        transform: translateY(-4px);
    }

    #guest-job-applicant-page .guest-job-pop {
        animation: guest-job-pop 0.58s cubic-bezier(0.22, 1, 0.36, 1) both;
        animation-delay: var(--guest-job-delay, 120ms);
    }

    #guest-job-applicant-page {
        max-width: 1180px;
    }

    #guest-job-applicant-page > h2 {
        margin-bottom: 1.25rem !important;
        color: #0f172a;
        font-size: clamp(2.1rem, 4vw, 3.5rem);
        font-weight: 900;
        letter-spacing: -0.045em;
    }

    #jobOpenSearchForm {
        margin-bottom: 1.5rem !important;
    }

    #jobOpenSearchForm .input-group {
        max-width: 760px !important;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
        padding: 0.35rem;
        overflow: hidden;
    }

    #jobOpenSearchForm .form-control {
        border: 0;
        border-radius: 14px !important;
        background: #f8fafc;
        min-height: 3rem;
        padding-left: 1rem;
        box-shadow: none;
    }

    #jobOpenSearchForm .form-control:focus {
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.11);
    }

    #jobOpenSearchForm .btn {
        border: 0;
        border-radius: 14px !important;
        background: linear-gradient(135deg, #0f766e, #16a34a);
        min-width: 7.5rem;
        font-weight: 800;
    }

    #guest-job-applicant-page .job-open-item {
        border: 1px solid rgba(148, 163, 184, 0.18) !important;
        border-radius: 22px;
        background:
            linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.94));
        box-shadow: 0 22px 60px rgba(15, 23, 42, 0.08) !important;
        overflow: hidden;
    }

    #guest-job-applicant-page .job-open-item.hover-card:hover {
        border-color: rgba(34, 197, 94, 0.5) !important;
        box-shadow:
            0 28px 70px rgba(15, 23, 42, 0.12),
            0 0 0 4px rgba(34, 197, 94, 0.12) !important;
    }

    #guest-job-applicant-page .job-open-item .card-body {
        padding: 1.35rem 1.45rem;
    }

    #guest-job-applicant-page .job-open-item h4 {
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 900;
        letter-spacing: -0.025em;
    }

    #guest-job-applicant-page .job-open-item h5 {
        color: #0f766e !important;
        font-size: 1rem;
        font-weight: 800;
    }

    #guest-job-applicant-page .job-open-item .rounded-pill {
        background: #dcfce7 !important;
        color: #047857 !important;
        font-weight: 900;
        min-width: 5.5rem;
    }

    #guest-job-applicant-page .job-open-item ul {
        margin-top: 1rem;
        color: #5b677a;
        line-height: 1.7;
    }

    #guest-job-applicant-page .job-open-item ul li {
        display: flex;
        gap: 0.65rem;
    }

    #guest-job-applicant-page .job-open-item ul li::before {
        content: "";
        width: 0.42rem;
        height: 0.42rem;
        border-radius: 999px;
        background: #10b981;
        flex: 0 0 0.42rem;
        margin-top: 0.62rem;
    }

    #guest-job-applicant-page .job-open-item .text-muted {
        color: #475569 !important;
        font-weight: 600;
    }

    #guest-job-applicant-page .job-open-item .badge.bg-light {
        border-radius: 999px;
        background: #f1f5f9 !important;
        color: #334155 !important;
        padding: 0.45rem 0.65rem;
        font-weight: 800;
    }

    #guest-job-applicant-page .view-details {
        border-radius: 999px;
        color: #047857 !important;
        font-weight: 900 !important;
        padding: 0.45rem 0.2rem;
    }

    #guest-job-applicant-page .view-details:hover {
        color: #0f172a !important;
    }

    @keyframes guest-job-fade-up {
        0% {
            opacity: 0;
            transform: translateY(26px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes guest-job-pop {
        0% {
            opacity: 0;
            transform: scale(0.9);
        }

        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #guest-job-applicant-page .guest-job-reveal,
        #guest-job-applicant-page .guest-job-card-motion,
        #guest-job-applicant-page .guest-job-pop,
        .site-footer.guest-job-reveal,
        .site-footer .guest-job-card-motion {
            opacity: 1;
            transform: none;
            transition: none;
            animation: none;
            will-change: auto;
        }
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
</style>

@include('layouts.header')

<div class="header-divider"></div>

<main id="guest-job-applicant-page" class="container my-5 animated-card2 delay-5">
    <h2 class="fw-bold mb-4">Job Vacancies</h2>

    <form id="jobOpenSearchForm" class="mb-4" role="search">
        <div class="input-group" style="max-width: 720px;">
            <input
                id="jobOpenSearchInput"
                type="search"
                class="form-control"
                placeholder="Search job titles, departments, locations..."
                aria-label="Search jobs"
            >
            <button class="btn btn-success" type="submit">Search</button>
        </div>
    </form>

<div class="job-open-item card shadow-lg mb-4 hover-card"
    data-title="{{ Str::lower($job->title) }}"
    data-department="{{ Str::lower($job->department) }}"
    data-location="{{ Str::lower($job->location) }}"
    data-description="{{ Str::lower($job->job_description) }}"
>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">{{ $job->title }}</h4>
                <h5 class="text-secondary mb-1">{{ $job->department }}</h5>
            </div>
            <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                {{ $job->employment }}
            </span>
        </div>

        @php
            $lines = preg_split("/\r\n|\n|\r/", $job->job_description);
        @endphp

        <ul class="list-unstyled ps-0 mb-2">
            @foreach (array_slice($lines, 0, 3) as $line)
                <li class="mb-1">
                    {{
                        Str::limit(
                            ltrim($line, "•- "),
                            282,
                            '...'
                        )
                    }}
                </li>
            @endforeach
        </ul>

        <p class="mb-1 text-muted">
            <i class="bi bi-geo-alt me-1"></i>{{ $job->location }}
        </p>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="badge bg-light text-dark">
                {{ $job->created_at->diffForHumans() }}
            </span>
            <a href="javascript:void(0)"
               class="fw-semibold text-success text-decoration-none view-details"
               data-job-id="{{ $job->id }}">
                View Details
            </a>
        </div>
    </div>
</div>

@foreach ($other as $others)
    <div class="job-open-item card shadow-lg mb-4 hover-card"
        data-title="{{ Str::lower($others->title) }}"
        data-department="{{ Str::lower($others->department) }}"
        data-location="{{ Str::lower($others->location) }}"
        data-description="{{ Str::lower($others->job_description) }}"
    >
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">{{ $others->title }}</h4>
                    <h5 class="text-secondary mb-1">{{ $others->department }}</h5>
                </div>
                <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                    {{ $others->employment }}
                </span>
            </div>

            @php
                $lines = preg_split("/\r\n|\n|\r/", $others->job_description);
            @endphp

            <ul class="list-unstyled ps-0 mb-2">
                @foreach (array_slice($lines, 0, 3) as $line)
                    <li class="mb-1">
                        {{
                            Str::limit(
                                ltrim($line, "•- "),
                                250,
                                '...'
                            )
                        }}
                    </li>
                @endforeach
            </ul>

            <p class="mb-1 text-muted">
                <i class="bi bi-geo-alt me-1"></i>{{ $others->location }}
            </p>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="badge bg-light text-dark">
                    {{ $others->created_at->diffForHumans() }}
                </span>
                <a href="javascript:void(0)"
                   class="fw-semibold text-success text-decoration-none view-details"
                   data-job-id="{{ $others->id }}">
                    View Details
                </a>
            </div>
        </div>
    </div>
@endforeach

<div id="jobOpenNoResults" class="alert alert-warning d-none">
    No jobs matched your search.
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

{{-- Overlay --}}
<div id="overlay"></div>

{{-- Sidebar --}}
<div id="jobSidebar">
    <div class="sidebar-header">
        <button type="button" class="close-btn" aria-label="Close job details">
            <i class="bi bi-x-lg"></i>
        </button>

        <h3 id="sidebarTitle"></h3>
        <h6 class="text-secondary" id="sidebarCollege"></h6>

        <div class="sidebar-meta-row">
            <span class="job-chip">
                <i class="bi bi-clock-fill"></i>
                <span id="sidebarType"></span>
            </span>
            <span class="job-chip">
                <i class="bi bi-geo-alt-fill"></i>
                <span id="sidebarLocationText"></span>
            </span>
        </div>

        <div class="sidebar-date-row">
            <span class="job-chip-outline">
                <strong>Date Posted:</strong>
                <span id="sidebarStartText"></span>
            </span>
            <span class="job-chip-outline">
                <strong>Date Expired:</strong>
                <span id="sidebarExpireText"></span>
            </span>
        </div>
    </div>

    <div class="sidebar-body">
        <div class="job-detail-card mb-4">
            <h6 class="mb-2 section-title-inline">Skill Requirements</h6>
            <div
                id="sidebarSkills"
                class="d-flex flex-wrap gap-2 justify-content-center mt-2">
            </div>
        </div>

        <div class="job-detail-card mb-4">
            <h6 class="section-title">Job Description</h6>
            <ul id="sidebarDescription" class="list-unstyled ps-0"></ul>

            <h6 class="section-title">Responsibilities</h6>
            <ul id="sidebarResponsibilities" class="list-unstyled ps-0"></ul>

            <h6 class="section-title">Qualifications</h6>
            <ul id="sidebarQualifications" class="list-unstyled ps-0"></ul>
        </div>

        <div class="text-center mt-3">
            <a href="javascript:void(0)" id="applyJobBtn" class="btn btn-success w-100">
                Apply Now
            </a>
            <p class="apply-subtext">Takes less than 3 minutes</p>
        </div>

        <hr>

        <h6 class="section-title">Related Jobs Open</h6>
        <div id="otherJobs"></div>
        <div id="relatedJobsEmpty" class="related-empty d-none">
            No related openings right now.
            <a href="{{ route('guest.index') }}">Browse all jobs</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const page = document.getElementById('guest-job-applicant-page');
        if (!page) return;

        const revealGroups = [
            ['h2', 0],
            ['#jobOpenSearchForm', 80],
            ['.job-open-item', 140],
            ['#jobOpenNoResults', 120],
        ];

        revealGroups.forEach(([selector, baseDelay]) => {
            page.querySelectorAll(selector).forEach((item, index) => {
                item.classList.add('guest-job-reveal');
                item.style.setProperty('--guest-job-delay', `${Math.min(baseDelay + ((index % 6) * 45), 420)}ms`);
            });
        });

        page.querySelectorAll('.job-open-item, .input-group, .badge, .view-details').forEach((item) => {
            item.classList.add('guest-job-card-motion');
        });

        page.querySelectorAll('.badge, .bi').forEach((item, index) => {
            item.classList.add('guest-job-pop');
            item.style.setProperty('--guest-job-delay', `${120 + ((index % 5) * 35)}ms`);
        });

        document.querySelectorAll('.site-footer .footer-contact, .site-footer .footer-link-list a, .site-footer .footer-bottom-links a').forEach((item) => {
            item.classList.add('guest-job-card-motion');
        });

        const animatedItems = Array.from(page.querySelectorAll('.guest-job-reveal'));
        const footer = document.querySelector('.site-footer');
        if (footer) {
            footer.classList.add('guest-job-reveal');
            footer.style.setProperty('--guest-job-delay', '180ms');
            animatedItems.push(footer);
        }

        if (!('IntersectionObserver' in window)) {
            animatedItems.forEach((item) => item.classList.add('is-scroll-animated'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('is-scroll-animated');
                    void entry.target.offsetWidth;
                    entry.target.classList.add('is-scroll-animated');
                } else {
                    entry.target.classList.remove('is-scroll-animated');
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -35px 0px',
        });

        animatedItems.forEach((item) => observer.observe(item));
    });

    const allJobs = @json($jobOpen);

    const sidebar = document.getElementById('jobSidebar');
    const overlay = document.getElementById('overlay');
    const searchForm = document.getElementById('jobOpenSearchForm');
    const searchInput = document.getElementById('jobOpenSearchInput');
    const jobCards = Array.from(document.querySelectorAll('.job-open-item'));
    const noResults = document.getElementById('jobOpenNoResults');

    function filterJobCards() {
        const searchTerm = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        jobCards.forEach((card) => {
            const searchableText = [
                card.dataset.title || '',
                card.dataset.department || '',
                card.dataset.location || '',
                card.dataset.description || '',
            ].join(' ');

            const isVisible = !searchTerm || searchableText.includes(searchTerm);
            card.classList.toggle('d-none', !isVisible);
            if (isVisible) visibleCount++;
        });

        noResults.classList.toggle('d-none', visibleCount > 0);
    }

    function populateList(id, data) {
        const el = document.getElementById(id);
        el.innerHTML = '';
        if (!data) return;

        // If responsibilities come as a single string with bullets/newlines, split into lines
        let items = Array.isArray(data) ? data : [data];

        // special handling for longer text fields: split on bullet markers or newlines
        if (/(responsib|description|qualif)/.test(id.toLowerCase())) {
            if (!Array.isArray(data)) {
                let text = String(data || '');
                // convert <br> to newline for consistent splitting
                text = text.replace(/<br\s*\/?/gi, '\n');

                // If the text contains explicit bullet markers, split on newlines and strip markers
                if (/\u2022|\u2023|\u25E6|^\s*[-–—\*\+]/m.test(text)) {
                    const parts = text.split(/\n+/).map(p => p.replace(/^\s*[\u2022\u2023\u25E6\-–—\*\+]\s*/, '').trim());
                    items = parts.filter(Boolean);
                } else {
                    // fallback: split on newlines only
                    const parts = text.split(/\n+/).map(p => p.trim());
                    items = parts.filter(Boolean);
                }
            }
        }

        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'mb-2';
            // allow HTML strings if passed, otherwise use text
            if (typeof item === 'string' && /<[^>]+>/.test(item)) {
                li.innerHTML = item;
            } else {
                li.textContent = item;
            }
            el.appendChild(li);
        });
    }

    function populateSkills(data) {
        const el = document.getElementById('sidebarSkills');
        el.innerHTML = '';
        if (!data) return;

        (Array.isArray(data) ? data : data.split(',')).forEach(skill => {
            const span = document.createElement('span');
            span.className = 'badge skill-badge';
            span.textContent = skill.trim();
            el.appendChild(span);
        });
    }

function populateOtherJobs(currentJob) {
    const container = document.getElementById('otherJobs');
    const emptyState = document.getElementById('relatedJobsEmpty');
    container.innerHTML = '';

    const relatedJobs = allJobs
        .filter(j =>
            j.id !== currentJob.id &&
            j.department === currentJob.department
        );

    if (!relatedJobs.length) {
        if (emptyState) emptyState.classList.remove('d-none');
        return;
    }

    if (emptyState) emptyState.classList.add('d-none');

    relatedJobs.forEach(job => {
            const div = document.createElement('div');
            div.className =
                'border rounded p-2 mb-2 d-flex justify-content-between align-items-center cursor-pointer';

            div.innerHTML = `
                <div>
                    <strong>${job.title}</strong><br>
                    <small class="text-muted">${job.department}</small>
                </div>
                <a href="javascript:void(0)"
                   class="text-success open-job"
                   style="text-decoration: none;"
                   data-job-id="${job.id}">
                    View →
                </a>
            `;

            container.appendChild(div);
        });
}
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    // options for formatting
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString(undefined, options);
}


    function openSidebar(job) {
        document.getElementById('sidebarTitle').textContent = job.title;
        document.getElementById('sidebarCollege').textContent = job.department;
        document.getElementById('sidebarType').textContent = job.employment;
        document.getElementById('sidebarLocationText').textContent = job.location;
        document.getElementById('sidebarStartText').textContent = formatDate(job.one);
        document.getElementById('sidebarExpireText').textContent = formatDate(job.two);

        populateSkills(job.skills);
        populateList('sidebarDescription', job.job_description);
        populateList('sidebarResponsibilities', job.responsibilities);
        populateList('sidebarQualifications', job.requirements);

        document.getElementById('applyJobBtn').href =
            `/application/non_teaching/procedure/${job.id}`;

        populateOtherJobs(job);

        sidebar.classList.add('show');
        overlay.style.display = 'block';
    }

    document.querySelectorAll('.view-details').forEach(btn => {
        btn.addEventListener('click', () => {
            const jobId = parseInt(btn.dataset.jobId);
            const job = allJobs.find(j => j.id === jobId);
            if (job) {
                openSidebar(job);
            }
        });
    });

    document.addEventListener('click', e => {
        if (e.target.classList.contains('open-job')) {
            const jobId = parseInt(e.target.dataset.jobId);
            const job = allJobs.find(j => j.id === jobId);
            if (job) {
                openSidebar(job);
            }
        }
    });

    document.querySelector('.close-btn').onclick = () => {
        sidebar.classList.remove('show');
        overlay.style.display = 'none';
    };

    overlay.onclick = () => {
        sidebar.classList.remove('show');
        overlay.style.display = 'none';
    };

    searchForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        filterJobCards();
    });

    searchInput?.addEventListener('input', filterJobCards);
</script>

@endsection


