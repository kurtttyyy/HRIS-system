@extends('layouts.app')

@section('page-loader')
<div id="page-loader" class="page-loader" role="status">
    <div class="loader-content">
        <div class="loader-icon">
            <div class="dot dot-1"></div>
            <div class="dot dot-2"></div>
            <div class="dot dot-3"></div>
        </div>
        <div class="loader-text">
            loading opportunities<span class="dots">...</span>
        </div>
    </div>
            </div>
@endsection
@push('loader-script')
<script>
(function(){
    const loader = document.getElementById('page-loader');
    if(!loader) return;

    const minDelay = 1000;
    const start = Date.now();

    function hideLoader(){
        const elapsed = Date.now() - start;
        const remaining = Math.max(0, minDelay - elapsed);
        setTimeout(()=>{
            loader.classList.add('fade-out');
            setTimeout(()=> loader.remove(), 350);
        }, remaining);
    }

    if(document.readyState === 'complete'){
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
    }
})();
</script>
@endpush


@section('content')
@include('layouts.header')  {{-- UNIVERSAL HEADER --}}
<div class="header-divider" aria-hidden="true"></div>

@if(session('popup_error'))
<div class="modal fade" id="applicationPopupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Application Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ session('popup_error') }}
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('show_rating_modal') || session('success') === 'Submitted successfully')
<style>
    .application-next-panel {
        border: 1px solid #bbf7d0;
        border-radius: 0.5rem;
        background: #f0fdf4;
        padding: 1rem;
    }
    .application-next-panel h6 {
        color: #166534;
        font-weight: 800;
        margin-bottom: 0.35rem;
    }
    .application-next-panel p {
        color: #374151;
        margin-bottom: 0;
    }
    .rating-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    .rating-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.6rem 0.8rem;
        cursor: pointer;
        background: #fff;
    }
    .rating-option:hover {
        background: #f9fafb;
        border-color: #86efac;
    }
    .btn-check:checked + .rating-option {
        border-color: #16a34a;
        background: #f0fdf4;
    }
    .rating-stars {
        color: #eab308;
        letter-spacing: 1px;
        font-size: 1rem;
        line-height: 1;
    }
    .rating-label {
        font-size: 0.9rem;
        color: #374151;
        font-weight: 600;
    }
</style>
<div class="modal fade" id="applicationNextModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Application Submitted</h5>
            </div>
            <div class="modal-body">
                <div class="application-next-panel">
                    <h6>Do you want to view your application?</h6>
                    <p>Your submitted details and uploaded documents can be reviewed in Application Status.</p>
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST" action="{{ route('guest.application.submit') }}" class="me-auto">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('application_review_email', session('applicant_email')) }}">
                    <button type="submit" class="btn btn-success">View Application</button>
                </form>
                <button type="button" class="btn btn-outline-secondary" id="continueToRatingBtn">Rate Now</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="applicationRatingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Your Application Experience</h5>
            </div>
            <form method="POST" action="{{ route('applicant.rating.store') }}">
                @csrf
                <div class="modal-body">
                    <p class="mb-1">Thank you for submitting your application. Please rate the system.</p>
                    <p class="mb-3">Thank you for applying. You can check your application at Application Status.</p>
                    <div class="rating-grid">
                        <input type="radio" class="btn-check" name="rating" id="rate5" value="5" required>
                        <label class="rating-option" for="rate5">
                            <span class="rating-label">Excellent</span>
                            <span class="rating-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                        </label>

                        <input type="radio" class="btn-check" name="rating" id="rate4" value="4" required>
                        <label class="rating-option" for="rate4">
                            <span class="rating-label">Very Good</span>
                            <span class="rating-stars">&#9733;&#9733;&#9733;&#9733;</span>
                        </label>

                        <input type="radio" class="btn-check" name="rating" id="rate3" value="3" required>
                        <label class="rating-option" for="rate3">
                            <span class="rating-label">Good</span>
                            <span class="rating-stars">&#9733;&#9733;&#9733;</span>
                        </label>

                        <input type="radio" class="btn-check" name="rating" id="rate2" value="2" required>
                        <label class="rating-option" for="rate2">
                            <span class="rating-label">Fair</span>
                            <span class="rating-stars">&#9733;&#9733;</span>
                        </label>

                        <input type="radio" class="btn-check" name="rating" id="rate1" value="1" required>
                        <label class="rating-option" for="rate1">
                            <span class="rating-label">Poor</span>
                            <span class="rating-stars">&#9733;</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Submit Rating</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
    .careers-page {
        --careers-ink: #0f172a;
        --careers-muted: #5b6475;
        --careers-line: rgba(15, 23, 42, 0.08);
        --careers-surface: rgba(255, 255, 255, 0.9);
        --careers-brand: #157347;
        --careers-brand-strong: #0d5c38;
        --careers-accent: #f4c542;
        --careers-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        background:
            radial-gradient(circle at top left, rgba(21, 115, 71, 0.12), transparent 28%),
            radial-gradient(circle at top right, rgba(244, 197, 66, 0.2), transparent 24%),
            linear-gradient(180deg, #eef7f0 0%, #f8fafc 22%, #ffffff 100%);
    }

    #jobList .hover-card:hover {
        border-color: #22c55e !important;
        box-shadow:
            0 28px 60px rgba(34, 197, 94, 0.45),
            0 0 0 6px rgba(34, 197, 94, 0.30),
            0 0 34px rgba(34, 197, 94, 0.42) !important;
    }

    #jobList .job-item.is-search-focused .job-card {
        border-color: #22c55e !important;
        box-shadow:
            0 28px 60px rgba(34, 197, 94, 0.36),
            0 0 0 5px rgba(34, 197, 94, 0.22) !important;
    }

    .careers-hero {
        min-height: 560px;
        padding: 6rem 0 7rem;
        display: flex;
        align-items: center;
    }

    .careers-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, rgba(4, 18, 16, 0.85), rgba(5, 76, 48, 0.6) 45%, rgba(11, 18, 32, 0.78));
        z-index: 1;
    }

    .careers-hero::after {
        content: "";
        position: absolute;
        inset: auto auto 2.5rem 6%;
        width: 16rem;
        height: 16rem;
        border-radius: 50%;
        background: rgba(244, 197, 66, 0.18);
        filter: blur(40px);
        z-index: 1;
    }

    .hero-backdrop {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.05), transparent 65%),
            linear-gradient(90deg, rgba(244, 197, 66, 0.1), transparent 40%);
        z-index: 2;
        pointer-events: none;
    }

    .hero-shell {
        position: relative;
        z-index: 3;
        max-width: 860px;
        margin: 0 auto;
        padding: 2.25rem;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 2rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.06));
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.22);
        backdrop-filter: blur(10px);
    }

    .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.55rem 1rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #fff3c4;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.16em;
    }

    .hero-kicker::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
        background: var(--careers-accent);
        box-shadow: 0 0 0 6px rgba(244, 197, 66, 0.18);
    }

    .hero-title {
        margin-top: 1.35rem;
        margin-bottom: 1rem;
        font-size: clamp(2.5rem, 5vw, 4.6rem);
        line-height: 1.04;
        font-weight: 800;
        color: #ffffff;
        text-shadow: 0 8px 30px rgba(0, 0, 0, 0.28);
    }

    .hero-highlight {
        color: #ffe082;
    }

    .hero-copy {
        max-width: 640px;
        margin: 0 auto 1.8rem;
        font-size: 1.05rem;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.88);
    }

    .hero-search-card {
        max-width: 760px;
        margin: 0 auto;
        padding: 0.9rem;
        border-radius: 1.5rem;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.16);
        box-shadow: 0 20px 55px rgba(0, 0, 0, 0.18);
        backdrop-filter: blur(10px);
        position: relative;
    }

    .search-unavailable-bubble {
        position: absolute;
        left: 1.2rem;
        right: 1.2rem;
        top: calc(100% + 0.75rem);
        z-index: 5;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        max-width: 520px;
        margin: 0 auto;
        padding: 0.85rem 1rem;
        border-radius: 1rem;
        background: #ffffff;
        border: 1px solid rgba(22, 163, 74, 0.22);
        color: #122038;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22);
        font-weight: 700;
        text-align: left;
    }

    .search-unavailable-bubble::before {
        content: "";
        position: absolute;
        top: -0.45rem;
        left: 50%;
        width: 0.9rem;
        height: 0.9rem;
        background: #ffffff;
        border-left: 1px solid rgba(22, 163, 74, 0.22);
        border-top: 1px solid rgba(22, 163, 74, 0.22);
        transform: translateX(-50%) rotate(45deg);
    }

    .search-unavailable-dot {
        width: 0.65rem;
        height: 0.65rem;
        flex: 0 0 0.65rem;
        border-radius: 999px;
        background: #ef4444;
        box-shadow: 0 0 0 0.35rem rgba(239, 68, 68, 0.12);
    }

    .hero-metrics {
        margin-top: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.85rem;
    }

    .hero-metric {
        padding: 0.8rem 1rem;
        min-width: 140px;
        border-radius: 1.15rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #fff;
        text-align: left;
    }

    button.hero-metric {
        cursor: pointer;
        font: inherit;
        appearance: none;
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    button.hero-metric:hover,
    button.hero-metric:focus-visible {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.18);
        border-color: rgba(255, 255, 255, 0.32);
        outline: none;
    }

    .filter-field.is-focused {
        border-color: rgba(34, 197, 94, 0.65);
        box-shadow: 0 18px 40px rgba(34, 197, 94, 0.2);
    }

    .hero-metric strong {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .hero-metric-rating {
        position: relative;
        display: inline-block;
        font-size: 0.9rem;
        line-height: 1;
        letter-spacing: 0.08em;
    }

    .hero-metric-rating-base {
        color: rgba(255, 255, 255, 0.28) !important;
    }

    .hero-metric-rating-fill {
        position: absolute;
        inset: 0 auto 0 0;
        overflow: hidden;
        color: #facc15 !important;
        text-shadow: 0 0 10px rgba(250, 204, 21, 0.35);
        white-space: nowrap;
    }

    .hero-metric span {
        display: block;
        margin-top: 0.15rem;
        color: rgba(255, 255, 255, 0.72);
        font-size: 0.82rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .filter-panel {
        position: relative;
        z-index: 4;
        max-width: 1160px;
        margin: 2rem auto 0;
        padding: 1.4rem;
        border-radius: 1.75rem;
        border: 1px solid #cfe3d7;
        background: linear-gradient(180deg, #f3f8f5, #edf5f1);
        box-shadow: var(--careers-shadow);
    }

    .filter-intro {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .filter-intro h2 {
        margin: 0;
        color: #0f2a1f;
        font-size: 1.35rem;
        font-weight: 800;
    }

    .filter-intro p {
        margin: 0.35rem 0 0;
        color: #5f6f67;
        font-size: 0.94rem;
    }

    .filter-chip {
        padding: 0.55rem 0.95rem;
        border-radius: 999px;
        background: #ddf6e8;
        color: #0f7a4c;
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .filter-field {
        padding: 1rem;
        height: 100%;
        border-radius: 1.2rem;
        border: 1px solid #cfe3d7;
        background: #ffffff;
    }

    .filter-field .form-label {
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0f7a4c;
    }

    .filter-field .form-select {
        border-radius: 0.95rem;
        border-color: #cfe3d7;
        color: #0f2a1f;
        background-color: #ffffff;
        min-height: 48px;
    }

    .filter-field .form-select:focus {
        border-color: #22a06b;
        box-shadow: 0 0 0 0.2rem rgba(34, 160, 107, 0.2);
    }

    .stats-shell {
        max-width: 1160px;
        margin: 1.5rem auto 0;
    }

    .stats-shell .stat-card {
        height: 100%;
        padding: 1.4rem 1.35rem;
        border: 1px solid rgba(15, 23, 42, 0.05);
        border-radius: 1.4rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(243, 247, 244, 0.96));
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.07);
        text-align: left;
    }

    .stats-shell .stat-label {
        display: block;
        margin-bottom: 0.7rem;
        color: var(--careers-muted);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .stats-shell .stat-number {
        color: var(--careers-ink);
        font-size: clamp(1.8rem, 3vw, 2.35rem);
        font-weight: 800;
        line-height: 1;
    }

    .job-section {
        max-width: 1160px;
        margin: 3rem auto 0;
        padding-bottom: 4rem;
    }


    .section-heading {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: end;
        margin-bottom: 1.75rem;
    }

    .section-heading h2 {
        margin: 0;
        color: var(--careers-ink);
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        font-weight: 800;
    }

    .section-heading p {
        margin: 0.55rem 0 0;
        max-width: 580px;
        color: var(--careers-muted);
        line-height: 1.7;
    }

    .section-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.7rem 1rem;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid var(--careers-line);
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
        color: var(--careers-brand-strong);
        font-weight: 700;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .job-card {
        position: relative;
        height: 100%;
        padding: 1.45rem;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1.6rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 250, 247, 0.98));
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }

    .job-card::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 1px;
        background: linear-gradient(135deg, rgba(21, 115, 71, 0.28), rgba(244, 197, 66, 0.16), rgba(21, 115, 71, 0.08));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }

    .job-card:hover {
        transform: translateY(-6px);
    }

    .job-card-top {
        display: flex;
        justify-content: space-between;
        gap: 0.8rem;
        align-items: start;
        margin-bottom: 1rem;
    }

    .job-card-title {
        margin: 0;
        color: var(--careers-ink);
        font-size: 1.2rem;
        font-weight: 800;
    }

    .job-card-dept {
        margin-top: 0.28rem;
        color: var(--careers-brand-strong);
        font-weight: 700;
    }

    .job-card-copy {
        list-style: none;
        padding: 0;
        margin: 0 0 1rem;
    }

    .job-card-copy li {
        position: relative;
        padding-left: 1rem;
        margin-bottom: 0.7rem;
        color: var(--careers-muted);
        line-height: 1.65;
    }

    .job-card-copy li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.72rem;
        width: 0.42rem;
        height: 0.42rem;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--careers-brand), #34d399);
    }

    .job-meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-bottom: 1.15rem;
    }

    .job-meta-pill {
        padding: 0.5rem 0.85rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, 0.06);
        color: var(--careers-ink);
        font-size: 0.82rem;
        font-weight: 700;
    }

    .job-card .green-btn {
        min-height: 48px;
        border-radius: 1rem !important;
        background: linear-gradient(135deg, var(--careers-brand) 0%, #22c55e 100%) !important;
        border: none !important;
        box-shadow: 0 16px 28px rgba(21, 115, 71, 0.2);
    }

    .empty-state {
        border: 1px solid rgba(245, 158, 11, 0.18);
        border-radius: 1.3rem;
        background: linear-gradient(180deg, rgba(255, 251, 235, 0.96), rgba(255, 255, 255, 0.96));
        color: #9a6700;
        box-shadow: 0 16px 34px rgba(245, 158, 11, 0.08);
    }

    #guest-index-page .guest-index-reveal {
        opacity: 1;
        transform: translateY(0);
        will-change: opacity, transform;
    }

    #guest-index-page .guest-index-reveal.is-scroll-animated {
        animation: guest-index-fade-up 0.72s cubic-bezier(0.22, 1, 0.36, 1) both;
        animation-delay: var(--guest-index-delay, 0ms);
    }

    #guest-index-page .guest-index-card-motion {
        transition:
            transform 0.25s ease,
            box-shadow 0.25s ease,
            border-color 0.25s ease;
    }

    #guest-index-page .guest-index-card-motion:hover {
        transform: translateY(-4px);
    }

    #guest-index-page .guest-index-pop {
        animation: guest-index-pop 0.58s cubic-bezier(0.22, 1, 0.36, 1) both;
        animation-delay: var(--guest-index-delay, 120ms);
    }

    @keyframes guest-index-fade-up {
        0% {
            opacity: 0;
            transform: translateY(26px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes guest-index-pop {
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
        #guest-index-page .guest-index-reveal,
        #guest-index-page .guest-index-pop,
        #guest-index-page .guest-index-card-motion {
            opacity: 1;
            transform: none;
            transition: none;
            animation: none;
            will-change: auto;
        }
    }

    .site-footer {
        background:
            radial-gradient(circle at top left, rgba(21, 115, 71, 0.12), transparent 24%),
            linear-gradient(180deg, #0f1113 0%, #0b0c0d 100%);
        color: rgba(255, 255, 255, 0.82);
        margin-top: 4rem;
        overflow: hidden;
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
        padding: 4rem 8rem 2.5rem 1.5rem;
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

    .footer-copy {
        max-width: 18rem;
        color: rgba(255, 255, 255, 0.72);
        line-height: 1.7;
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
        min-width: 0;
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

    .nc-chatbot {
        position: fixed;
        right: 5.2rem;
        bottom: 2.4rem;
        z-index: 1085;
        width: 5.8rem;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .nc-chatbot-launcher {
        width: 6.2rem;
        height: 6.2rem;
        border: 0;
        border-radius: 50%;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background:
            radial-gradient(circle at 26% 22%, #ffffff 0%, #f8fafc 42%, #e5e7eb 100%);
        box-shadow:
            0 18px 32px rgba(15, 23, 42, 0.28),
            inset 0 2px 5px rgba(255, 255, 255, 0.85),
            inset 0 -8px 14px rgba(148, 163, 184, 0.22),
            0 0 0 4px rgba(255, 255, 255, 0.88);
        color: #0f172a;
        cursor: pointer;
        animation: nc-chatbot-float 2.8s ease-in-out infinite;
        overflow: hidden;
        transform-style: preserve-3d;
    }

    .nc-chatbot-launcher::before {
        content: "";
        position: absolute;
        inset: 0.4rem;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 24%, rgba(255, 255, 255, 0.9), rgba(241, 245, 249, 0.45) 70%, rgba(203, 213, 225, 0.28));
        box-shadow: inset 0 -6px 10px rgba(148, 163, 184, 0.22);
        z-index: 0;
    }

    .nc-chatbot-launcher::after {
        content: "";
        position: absolute;
        top: 0.6rem;
        left: 0.9rem;
        width: 2.15rem;
        height: 1.1rem;
        border-radius: 999px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.78), rgba(255, 255, 255, 0));
        transform: rotate(-18deg) translateZ(12px);
        pointer-events: none;
        z-index: 2;
    }

    .nc-robot {
        position: relative;
        width: 4.15rem;
        height: 2.95rem;
        border-radius: 1.45rem;
        background: linear-gradient(155deg, #1f2937 0%, #0f172a 58%, #020617 100%);
        border: 2px solid #0f172a;
        box-shadow:
            inset 0 0 0 2px rgba(74, 222, 128, 0.12),
            inset 0 8px 10px rgba(255, 255, 255, 0.08),
            0 8px 14px rgba(2, 6, 23, 0.45);
        transform-origin: center;
        transition: transform 0.15s ease-out;
        will-change: transform;
        z-index: 1;
    }

    .nc-robot::before {
        content: "";
        position: absolute;
        left: 0.42rem;
        right: 0.42rem;
        top: 0.28rem;
        height: 0.42rem;
        border-radius: 999px;
        background: linear-gradient(180deg, rgba(148, 163, 184, 0.38), rgba(148, 163, 184, 0));
        pointer-events: none;
    }

    .nc-robot-eye {
        position: absolute;
        top: 0.74rem;
        width: 0.66rem;
        height: 0.66rem;
        border-radius: 50%;
        transform: translate(var(--eye-x, 0px), var(--eye-y, 0px));
        transition: transform 0.15s ease-out;
        will-change: transform;
        border: 1px solid rgba(134, 239, 172, 0.8);
        background: rgba(2, 6, 23, 0.5);
        overflow: hidden;
    }

    .nc-robot-eye.left {
        left: 0.7rem;
    }

    .nc-robot-eye.right {
        right: 0.7rem;
    }

    .nc-robot-eye-core {
        position: absolute;
        inset: 0.06rem;
        border-radius: 50%;
        background: radial-gradient(circle at 35% 30%, #a5f3fc, #22d3ee 60%, #0891b2 100%);
        box-shadow: 0 0 10px rgba(34, 211, 238, 0.78);
        transform-origin: center;
        animation: nc-eye-blink 3.6s infinite;
    }

    .nc-robot-eye-core::before {
        content: "";
        display: none;
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: center / contain no-repeat
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cpath d='M51 50c10-9 20 2 14 12-5 8-18 6-22-2-5-11 6-20 17-20 13 0 23 11 23 24S73 86 58 86 30 74 30 58s12-30 29-30' fill='none' stroke='%2367e8f9' stroke-width='8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    }

    .nc-robot-mouth {
        position: absolute;
        left: 50%;
        bottom: 0.6rem;
        transform: translateX(-50%);
        width: 1.25rem;
        height: 0.28rem;
        border-radius: 999px;
        background: linear-gradient(90deg, #22c55e, #4ade80);
        box-shadow:
            0 0 8px rgba(74, 222, 128, 0.65),
            inset 0 -1px 2px rgba(15, 23, 42, 0.45);
        transition: all 0.22s ease;
    }

    .nc-chatbot-launcher:hover .nc-robot-mouth {
        width: 1.32rem;
        height: 0.46rem;
        bottom: 0.33rem;
        background: transparent;
        border-bottom: 0.18rem solid #4ade80;
        border-radius: 0 0 999px 999px;
        box-shadow:
            0 0 10px rgba(74, 222, 128, 0.78),
            none;
    }

    .nc-robot.is-sad .nc-robot-mouth {
        width: 1.12rem;
        height: 0.32rem;
        bottom: 0.5rem;
        background: transparent;
        border-top: 0.14rem solid #4ade80;
        border-radius: 999px 999px 0 0;
        box-shadow: 0 0 6px rgba(74, 222, 128, 0.55);
    }

    .nc-chatbot-launcher-label {
        position: absolute;
        left: 50%;
        top: 6.9rem;
        transform: translateX(-50%);
        white-space: nowrap;
        color: #047857;
        font-size: 0.65rem;
        font-family: "Trebuchet MS", "Gill Sans", "Segoe UI", sans-serif;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    .nc-chatbot-launcher,
    .nc-chatbot-launcher-label {
        transition: opacity 0.2s ease, transform 0.28s ease;
    }

    .nc-chatbot-help-hint {
        position: absolute;
        left: 50%;
        top: -3.05rem;
        transform: translate(-50%, 6px);
        max-width: 14rem;
        padding: 0.45rem 0.65rem;
        border-radius: 0.75rem;
        background: rgba(15, 23, 42, 0.94);
        color: #ecfdf5;
        border: 1px solid rgba(52, 211, 153, 0.45);
        box-shadow: 0 12px 25px rgba(2, 6, 23, 0.35);
        font-size: 0.72rem;
        line-height: 1.3;
        font-weight: 600;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s ease;
        pointer-events: none;
        text-align: center;
        white-space: nowrap;
        z-index: 3;
    }

    .nc-chatbot-help-hint.is-visible {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
    }

    .nc-chatbot-panel {
        position: absolute;
        right: 0;
        bottom: 5.2rem;
        width: min(24rem, calc(100vw - 1.5rem));
        max-height: min(34rem, calc(100vh - 8.5rem));
        display: grid;
        grid-template-rows: auto 1fr auto auto;
        border-radius: 1.15rem;
        overflow: hidden;
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 28px 50px rgba(15, 23, 42, 0.26);
        transform-origin: 88% 100%;
        will-change: transform, opacity;
    }

    .nc-chatbot-panel[hidden] {
        display: none;
    }

    .nc-chatbot-panel.pop-in {
        animation: nc-bubble-pop 0.34s cubic-bezier(0.2, 0.85, 0.25, 1.15);
    }

    .nc-chatbot.is-open .nc-chatbot-launcher,
    .nc-chatbot.is-open .nc-chatbot-launcher-label,
    .nc-chatbot.is-open .nc-chatbot-help-hint {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: scale(0.88);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .nc-chatbot.rolling-back .nc-chatbot-launcher {
        animation: nc-assistant-roll-3x 1.05s cubic-bezier(0.25, 0.8, 0.3, 1.02);
    }

    .nc-chatbot.rolling-back .nc-chatbot-launcher-label {
        animation: nc-assistant-label-return 1.05s ease;
    }

    .nc-chatbot.dizzy .nc-robot {
        animation: nc-assistant-dizzy 0.9s ease-in-out;
    }

    .nc-chatbot.dizzy .nc-robot-eye-core {
        animation: nc-eye-dizzy 0.95s cubic-bezier(0.22, 1.3, 0.36, 1);
        background: rgba(2, 6, 23, 0.08);
        box-shadow: none;
    }

    .nc-chatbot.dizzy .nc-robot-eye-core::before {
        display: block;
    }

    .nc-chatbot.dizzy .nc-robot-eye {
        animation: nc-eye-spring-dizzy 0.95s cubic-bezier(0.22, 1.45, 0.36, 1);
    }

    .nc-chatbot.dizzy .nc-robot-mouth {
        width: 1.45rem;
        height: 0.34rem;
        bottom: 0.46rem;
        background: transparent;
        border-top: 0.14rem solid #4ade80;
        border-radius: 65% 35% 60% 40% / 45% 55% 45% 55%;
        box-shadow: 0 0 8px rgba(74, 222, 128, 0.45);
        animation: nc-dizzy-mouth-wave 0.95s ease-in-out;
    }

    .nc-chatbot-header {
        padding: 0.95rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(130deg, #0f5132, #157347);
        color: #fff;
    }

    .nc-chatbot-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .nc-chatbot-subtitle {
        margin: 0.1rem 0 0;
        font-size: 0.75rem;
        color: rgba(226, 232, 240, 0.95);
    }

    .nc-chatbot-close {
        border: 0;
        border-radius: 0.55rem;
        width: 2rem;
        height: 2rem;
        color: #fff;
        background: rgba(255, 255, 255, 0.16);
    }

    .nc-chatbot-messages {
        padding: 0.95rem;
        overflow-y: auto;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
    }

    .nc-bubble {
        max-width: 88%;
        border-radius: 0.9rem;
        padding: 0.65rem 0.75rem;
        margin-bottom: 0.65rem;
        line-height: 1.45;
        font-size: 0.89rem;
        word-break: break-word;
    }

    .nc-bubble.user {
        margin-left: auto;
        background: #157347;
        color: #fff;
        border-bottom-right-radius: 0.25rem;
    }

    .nc-bubble.bot {
        background: #fff;
        color: #0f172a;
        border: 1px solid #dbe2ea;
        border-bottom-left-radius: 0.25rem;
    }

    .nc-chatbot-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        padding: 0.65rem 0.85rem 0.4rem;
        background: #f1f5f9;
        border-top: 1px solid #e2e8f0;
    }

    .nc-chatbot-chip {
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 999px;
        font-size: 0.73rem;
        line-height: 1;
        font-weight: 600;
        padding: 0.42rem 0.6rem;
        color: #334155;
    }

    .nc-chatbot-form {
        padding: 0.75rem;
        display: flex;
        align-items: flex-end;
        gap: 0.6rem;
        background: #fff;
        border-top: 1px solid #e2e8f0;
    }

    .nc-chatbot-input {
        flex: 1;
        resize: none;
        max-height: 8rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.85rem;
        padding: 0.6rem 0.7rem;
        font-size: 0.9rem;
        outline: none;
    }

    .nc-chatbot-input:focus {
        border-color: rgba(21, 115, 71, 0.55);
        box-shadow: 0 0 0 0.18rem rgba(21, 115, 71, 0.12);
    }

    .nc-chatbot-send {
        border: 0;
        border-radius: 0.85rem;
        min-width: 3.2rem;
        height: 2.9rem;
        background: linear-gradient(135deg, #157347, #1ea55d);
        color: #fff;
        font-weight: 700;
        font-size: 0.84rem;
        letter-spacing: 0.03em;
    }

    .nc-chatbot-send[disabled] {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .nc-typing {
        display: inline-flex;
        gap: 0.25rem;
        align-items: center;
    }

    .nc-typing i {
        width: 0.36rem;
        height: 0.36rem;
        border-radius: 50%;
        background: #94a3b8;
        animation: nc-dot 1s infinite ease-in-out;
    }

    .nc-typing i:nth-child(2) { animation-delay: 0.12s; }
    .nc-typing i:nth-child(3) { animation-delay: 0.24s; }

    @keyframes nc-dot {
        0%, 80%, 100% { transform: translateY(0); opacity: 0.45; }
        40% { transform: translateY(-4px); opacity: 1; }
    }

    @keyframes nc-chatbot-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    @keyframes nc-bubble-pop {
        0% {
            opacity: 0;
            transform: translateY(12px) scale(0.72);
        }
        68% {
            opacity: 1;
            transform: translateY(-2px) scale(1.04);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes nc-assistant-roll-3x {
        0% { transform: translateY(14px) scale(0.8) rotate(0deg); opacity: 0.1; }
        70% { transform: translateY(-2px) scale(1.02) rotate(990deg); opacity: 1; }
        100% { transform: translateY(0) scale(1) rotate(1080deg); opacity: 1; }
    }

    @keyframes nc-assistant-label-return {
        0% { transform: translateX(-50%) translateY(12px); opacity: 0; }
        70% { transform: translateX(-50%) translateY(-2px); opacity: 1; }
        100% { transform: translateX(-50%) translateY(0); opacity: 1; }
    }

    @keyframes nc-assistant-dizzy {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        20% { transform: translate(-2px, 0) rotate(-7deg); }
        40% { transform: translate(2px, 0) rotate(7deg); }
        60% { transform: translate(-1px, 0) rotate(-5deg); }
        80% { transform: translate(1px, 0) rotate(5deg); }
    }

    @keyframes nc-eye-dizzy {
        0% { opacity: 1; transform: scale(1) rotate(0deg); }
        18% { opacity: 0.9; transform: scale(1.24, 0.76) rotate(115deg); }
        36% { opacity: 0.85; transform: scale(0.82, 1.2) rotate(228deg); }
        56% { opacity: 0.95; transform: scale(1.12, 0.9) rotate(320deg); }
        74% { opacity: 0.9; transform: scale(0.92, 1.08) rotate(372deg); }
        100% { opacity: 1; transform: scale(1) rotate(410deg); }
    }

    @keyframes nc-eye-spring-dizzy {
        0% {
            transform: translate(var(--eye-x, 0px), var(--eye-y, 0px));
        }
        20% {
            transform: translate(calc(var(--eye-x, 0px) + 3px), calc(var(--eye-y, 0px) - 2px));
        }
        38% {
            transform: translate(calc(var(--eye-x, 0px) - 4px), calc(var(--eye-y, 0px) + 2px));
        }
        56% {
            transform: translate(calc(var(--eye-x, 0px) + 2px), calc(var(--eye-y, 0px) - 1px));
        }
        74% {
            transform: translate(calc(var(--eye-x, 0px) - 1px), calc(var(--eye-y, 0px) + 1px));
        }
        100% {
            transform: translate(var(--eye-x, 0px), var(--eye-y, 0px));
        }
    }

    @keyframes nc-dizzy-mouth-wave {
        0% {
            transform: translateX(-50%) translateY(0) rotate(0deg);
        }
        25% {
            transform: translateX(-50%) translateY(0.5px) rotate(-4deg);
        }
        50% {
            transform: translateX(-50%) translateY(0) rotate(4deg);
        }
        75% {
            transform: translateX(-50%) translateY(0.5px) rotate(-3deg);
        }
        100% {
            transform: translateX(-50%) translateY(0) rotate(0deg);
        }
    }

    @keyframes nc-eye-blink {
        0%, 44%, 100% { transform: scaleY(1); opacity: 1; }
        46%, 50% { transform: scaleY(0.1); opacity: 0.9; }
        52%, 56% { transform: scaleY(1); opacity: 1; }
    }

    @media (max-width: 991.98px) {
        .careers-hero {
            min-height: 520px;
            padding: 5rem 0 6rem;
        }

        .filter-panel {
            margin-top: 1.5rem;
        }

        .section-heading,
        .filter-intro {
            flex-direction: column;
            align-items: start;
        }

        .footer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .footer-shell {
            padding-right: 6.5rem;
        }
    }

    @media (max-width: 767.98px) {
        .careers-page {
            overflow-x: hidden;
        }

        .careers-hero {
            min-height: auto;
            padding: 3rem 0 4.5rem;
        }

        .careers-hero::after {
            width: 10rem;
            height: 10rem;
            left: 1rem;
            bottom: 1rem;
        }

        .careers-hero .container,
        .careers-page > .container,
        .job-section {
            max-width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .hero-shell {
            width: 100%;
            padding: 1.15rem;
            border-radius: 1.1rem;
        }

        .hero-kicker {
            max-width: 100%;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            font-size: 0.68rem;
            letter-spacing: 0.1em;
            white-space: normal;
        }

        .hero-title {
            font-size: clamp(2rem, 12vw, 3rem);
            line-height: 1.08;
        }

        .hero-copy {
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .hero-search-card {
            width: 100%;
            padding: 0.7rem;
            border-radius: 1.2rem;
        }

        .search-unavailable-bubble {
            position: static;
            margin-top: 0.75rem;
            font-size: 0.9rem;
        }

        .search-unavailable-bubble::before {
            display: none;
        }

        .search-input {
            flex-direction: column;
        }

        .search-input .form-control,
        .search-input .btn-hero {
            width: 100%;
            border-radius: 1rem !important;
        }

        .search-input .btn-hero {
            margin-left: 0;
            margin-top: 0.7rem;
        }

        .hero-metric {
            min-width: calc(50% - 0.5rem);
            padding: 0.8rem;
            border-radius: 0.95rem;
        }

        .filter-panel {
            margin-top: 1.25rem;
            padding: 1rem;
            border-radius: 1.35rem;
        }

        .filter-chip,
        .section-pill {
            width: 100%;
            justify-content: center;
            text-align: center;
            white-space: normal;
        }

        .stats-shell {
            margin-top: 1rem;
        }

        .job-section {
            margin-top: 2.4rem;
            padding-bottom: 2.5rem;
        }

        .job-card {
            padding: 1.15rem;
        }

        .job-card-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .footer-shell {
            padding: 3rem 1rem 7.25rem;
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

        .nc-chatbot {
            right: 2.7rem;
            bottom: 1.8rem;
            width: 5.1rem;
        }

        .nc-chatbot-launcher {
            width: 5.2rem;
            height: 5.2rem;
        }

        .nc-chatbot-launcher-label {
            display: block;
            top: 5.6rem;
            left: 50%;
            right: auto;
            transform: translateX(-50%);
            font-size: 0.6rem;
        }

        .nc-chatbot-help-hint {
            top: -2.7rem;
            left: 50%;
            right: auto;
            transform: translate(-50%, 6px);
            font-size: 0.67rem;
            padding: 0.42rem 0.55rem;
        }

        .nc-chatbot-help-hint.is-visible {
            transform: translate(-50%, 0);
        }

        .nc-chatbot-panel {
            right: -0.2rem;
            width: min(23rem, calc(100vw - 1rem));
            bottom: 4.8rem;
        }
    }

    @media (max-width: 575.98px) {
        .careers-hero {
            padding-top: 2rem;
            padding-bottom: 3.7rem;
        }

        .hero-shell {
            padding: 1rem;
        }

        .hero-metric {
            width: 100%;
            min-width: 100%;
        }

        .filter-panel {
            margin-top: 1rem;
        }

        .section-heading h2 {
            font-size: 1.65rem;
        }
    }
</style>

<main id="guest-index-page" class="careers-page">
<section class="hero careers-hero text-white position-relative overflow-hidden">

    <!-- Carousel Background -->
    <div id="heroCarousel" class="carousel slide carousel-fade position-absolute top-0 start-0 w-100 h-100"
         data-bs-ride="carousel" data-bs-interval="4000">

        <div class="carousel-inner h-100">
            <div class="carousel-item active h-90">
                <img src="{{ asset('images/banner2.png') }}"
                     class="d-block w-100 h-90 object-fit-cover carousel-dark-img"
                     alt="Careers">
            </div>
            <div class="carousel-item h-90">
                <img src="{{ asset('images/sirV.png') }}"
                     class="d-block w-100 h-90 object-fit-cover "
                     alt="Team">
            </div>
            <div class="carousel-item h-90">
                <img src="{{ asset('images/carousel.jpg') }}"
                     class="d-block w-100 h-90 object-fit-cover carousel-dark-img"
                     alt="Growth">
            </div>
        </div>
    </div>

    <div class="hero-backdrop"></div>

    <!-- Hero Content -->
    <div class="container text-center position-relative z-3">
        <div class="hero-shell animated-card2 delay-5">
            <span class="hero-kicker">Career Opportunities 2026</span>
            <h1 class="hero-title">
                Find a role where your <span class="hero-highlight">skills can grow</span>
            </h1>

            <p class="hero-copy">
                Explore meaningful opportunities, discover the teams behind them, and take the next confident step in your professional journey with us.
            </p>

            <form id="jobSearchForm" class="hero-search-card animated-card2 delay-5" role="search">
                <div class="input-group search-input">
                    <input id="jobSearchInput" type="search" class="form-control"
                           placeholder="Search job titles, keywords..."
                           aria-label="Search">
                    <button class="btn btn-hero" type="submit">Search</button>
                </div>
                <div id="searchUnavailableBubble" class="search-unavailable-bubble d-none" role="status" aria-live="polite">
                    <span class="search-unavailable-dot" aria-hidden="true"></span>
                    <span id="searchUnavailableText">That position is not available right now.</span>
                </div>
            </form>

            <div class="hero-metrics">
                <button type="button" class="hero-metric" id="openPositionsMetric" aria-label="Go to Job Vacancies">
                    <strong>{{ $openCount }}</strong>
                    <span>Open Positions</span>
                </button>
                <button type="button" class="hero-metric" id="departmentsMetric" aria-label="Show all departments">
                    <strong>{{ $department }}</strong>
                    <span>Departments</span>
                </button>
                <div class="hero-metric">
                    <strong>{{ $employee }}</strong>
                    <span>Employees</span>
                </div>
                <div class="hero-metric">
                    @php
                        $ratingValue = is_null($companyRating ?? null) ? 0.0 : max(0, min(5, (float) $companyRating));
                        $ratingWidth = ($ratingValue / 5) * 100;
                    @endphp
                    <strong>
                        <span>{{ number_format($ratingValue, 1) }}</span>
                        <span class="hero-metric-rating" aria-label="{{ number_format($ratingValue, 1) }} out of 5 stars">
                            <span class="hero-metric-rating-base">★★★★★</span>
                            <span class="hero-metric-rating-fill" style="width: {{ number_format($ratingWidth, 2, '.', '') }}%;">★★★★★</span>
                        </span>
                    </strong>
                    <span>Company Rating</span>
                </div>
            </div>
        </div>
    </div>
</section>


            <div class="container position-relative z-2 animated-card2 delay-5">
            <div class="filter-panel">
                <div class="filter-intro">
                    <div>
                        <h2>Refine Your Search</h2>
                        <p>Filter openings by department, employment type, and location to quickly find the role that fits you best.</p>
                    </div>
                    <span id="jobOpportunitiesCount" class="filter-chip">{{ $open_position->count() }} opportunities available</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="filter-field">
                            <label class="form-label small mb-1">Department</label>
                            <select id="departmentFilter" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($open_position->pluck('department')->unique() as $departments)
                                    <option value="{{ $departments }}">{{ $departments }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-field">
                            <label class="form-label small mb-1">Employment Type</label>
                            <select id="employmentFilter" class="form-select">
                                <option value="">All Types</option>
                                @foreach($open_position->pluck('employment')->unique() as $employments)
                                    <option value="{{ $employments }}">{{ $employments }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-field">
                            <label class="form-label small mb-1">Location</label>
                            <select id="locationFilter" class="form-select">
                                <option value="">All Location</option>
                                @foreach($open_position->pluck('location')->unique() as $locations)
                                    <option value="{{ $locations }}">{{ $locations }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
</div>
        </div>

<div id="jobVacanciesSection" class="container job-section">
    <div class="section-heading">
        <div>
            <h2>Job Vacancies</h2>
            <p>Browse our latest openings and choose the path that matches your strengths, goals, and preferred work environment.</p>
        </div>
        <div class="section-pill">Updated opportunities across multiple teams</div>
    </div>

    <div id="jobList" class="row g-4">
        @include('guest.partials.job-vacancies-list', ['open_position' => $featuredOpenPositions ?? $open_position->take(6)])
    </div>

    <div id="jobSeeMoreWrap" class="mt-5 text-center {{ ($hasMoreOpenPositions ?? $open_position->count() > 6) ? '' : 'd-none' }}">
        <a id="jobSeeMoreLink" href="{{ route('guest.jobOpenLanding') }}" class="text-decoration-none fw-bold text-success">
            Do you want to see more?
        </a>
    </div>

    <div id="noResultsMessage" class="alert empty-state mt-4 d-none" role="alert">
        That position is not available right now.
    </div>
</div>

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
                    <li><a href="#heroCarousel">Home</a></li>
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

<div class="nc-chatbot" id="ncChatbot" data-endpoint="{{ route('guest.chat.reply') }}">
    <span class="nc-chatbot-help-hint" id="ncChatHelpHint">Click me if you need help</span>
    <button class="nc-chatbot-launcher" id="ncChatLauncher" type="button" aria-expanded="false" aria-controls="ncChatPanel" aria-label="Open chat assistant">
        <span class="nc-robot" aria-hidden="true">
            <span class="nc-robot-eye left"><span class="nc-robot-eye-core"></span></span>
            <span class="nc-robot-eye right"><span class="nc-robot-eye-core"></span></span>
            <span class="nc-robot-mouth"></span>
        </span>
    </button>
    <span class="nc-chatbot-launcher-label">Click here to chat</span>

    <section class="nc-chatbot-panel" id="ncChatPanel" hidden>
        <header class="nc-chatbot-header">
            <div>
                <p class="nc-chatbot-title">NC Career Assistant</p>
                <p class="nc-chatbot-subtitle">Ask about jobs, requirements, and policies</p>
            </div>
            <button class="nc-chatbot-close" id="ncChatClose" type="button" aria-label="Close chat">X</button>
        </header>

        <div class="nc-chatbot-messages" id="ncChatMessages"></div>

        <div class="nc-chatbot-chips" id="ncChatChips">
            <button class="nc-chatbot-chip" type="button" data-msg="Show available jobs">Show available jobs</button>
            <button class="nc-chatbot-chip" type="button" data-msg="How to apply">How to apply</button>
            <button class="nc-chatbot-chip" type="button" data-msg="Application requirements">Requirements</button>
            <button class="nc-chatbot-chip" type="button" data-msg="Explain this website">Website guide</button>
            <button class="nc-chatbot-chip" type="button" data-msg="How to create an account">Account help</button>
            <button class="nc-chatbot-chip" type="button" data-msg="Where are policy pages?">Policy links</button>
        </div>

        <form class="nc-chatbot-form" id="ncChatForm">
            <textarea class="nc-chatbot-input" id="ncChatInput" rows="1" maxlength="500" placeholder="Type your message..."></textarea>
            <button class="nc-chatbot-send" id="ncChatSend" type="submit">Send</button>
        </form>
    </section>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const page = document.getElementById('guest-index-page');
        if (!page) return;

        const revealGroups = [
            ['.hero-shell', 0],
            ['.hero-search-card', 90],
            ['.hero-metric', 140],
            ['.filter-panel', 120],
            ['.section-heading', 160],
            ['.site-footer', 180],
        ];

        revealGroups.forEach(([selector, baseDelay]) => {
            page.querySelectorAll(selector).forEach((item, index) => {
                item.classList.add('guest-index-reveal');
                item.style.setProperty('--guest-index-delay', `${Math.min(baseDelay + ((index % 6) * 45), 420)}ms`);
            });
        });

        page.querySelectorAll('.hero-metric, .filter-field, .job-card, .section-pill, .footer-contact, .footer-link-list a, .footer-bottom-links a').forEach((item) => {
            item.classList.add('guest-index-card-motion');
        });

        page.querySelectorAll('.hero-kicker, .filter-chip, .badge').forEach((item, index) => {
            item.classList.add('guest-index-pop');
            item.style.setProperty('--guest-index-delay', `${120 + ((index % 4) * 40)}ms`);
        });

        const animatedItems = Array.from(page.querySelectorAll('.guest-index-reveal'));
        page.querySelectorAll('#jobList > .job-item').forEach((item) => {
            item.classList.add('guest-index-reveal', 'is-scroll-animated');
        });

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
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const jobSearchForm = document.getElementById('jobSearchForm');
        const searchInput = document.getElementById('jobSearchInput');
        const openPositionsMetric = document.getElementById('openPositionsMetric');
        const departmentsMetric = document.getElementById('departmentsMetric');
        const jobVacanciesSection = document.getElementById('jobVacanciesSection');
        const departmentFilter = document.getElementById('departmentFilter');
        const employmentFilter = document.getElementById('employmentFilter');
        const locationFilter = document.getElementById('locationFilter');
        const jobList = document.getElementById('jobList');
        const jobOpportunitiesCount = document.getElementById('jobOpportunitiesCount');
        const jobSeeMoreWrap = document.getElementById('jobSeeMoreWrap');
        const jobSeeMoreLink = document.getElementById('jobSeeMoreLink');
        const noResultsMessage = document.getElementById('noResultsMessage');
        const searchUnavailableBubble = document.getElementById('searchUnavailableBubble');
        const searchUnavailableText = document.getElementById('searchUnavailableText');
        let searchBubbleTimer = null;

        function normalize(value) {
            return (value || '').toString().trim().toLowerCase();
        }

        function hideSearchBubble() {
            if (searchBubbleTimer) {
                clearTimeout(searchBubbleTimer);
                searchBubbleTimer = null;
            }

            searchUnavailableBubble?.classList.add('d-none');
        }

        function collapseDepartmentOptions() {
            if (!departmentFilter) {
                return;
            }

            departmentFilter.removeAttribute('size');
            departmentFilter.closest('.filter-field')?.classList.remove('is-focused');
        }

        function expandDepartmentOptions() {
            if (!departmentFilter) {
                return;
            }

            departmentFilter.closest('.filter-field')?.classList.add('is-focused');
            departmentFilter.size = Math.min(Math.max(departmentFilter.options.length, 2), 6);
            departmentFilter.focus({ preventScroll: true });
        }

        function showSearchBubble(message) {
            if (!searchUnavailableBubble) {
                return;
            }

            if (searchUnavailableText) {
                searchUnavailableText.textContent = message;
            }

            searchUnavailableBubble.classList.remove('d-none');
            if (searchBubbleTimer) {
                clearTimeout(searchBubbleTimer);
            }

            searchBubbleTimer = setTimeout(hideSearchBubble, 4200);
        }

        function applyFilters(options = {}) {
            const searchTerm = normalize(searchInput?.value);
            const selectedDepartment = normalize(departmentFilter?.value);
            const selectedEmployment = normalize(employmentFilter?.value);
            const selectedLocation = normalize(locationFilter?.value);
            const jobItems = Array.from(document.querySelectorAll('.job-item'));

            let visibleCount = 0;
            let firstMatch = null;

            jobItems.forEach((item) => {
                item.classList.remove('is-search-focused');
                const title = normalize(item.dataset.title);
                const department = normalize(item.dataset.department);
                const employment = normalize(item.dataset.employment);
                const location = normalize(item.dataset.location);
                const description = normalize(item.dataset.description);

                const matchesSearch = !searchTerm ||
                    title.includes(searchTerm) ||
                    description.includes(searchTerm) ||
                    department.includes(searchTerm) ||
                    location.includes(searchTerm);

                const matchesDepartment = !selectedDepartment || department === selectedDepartment;
                const matchesEmployment = !selectedEmployment || employment === selectedEmployment;
                const matchesLocation = !selectedLocation || location === selectedLocation;

                const isVisible = matchesSearch && matchesDepartment && matchesEmployment && matchesLocation;

                item.classList.toggle('d-none', !isVisible);
                if (isVisible) {
                    visibleCount++;
                    firstMatch = firstMatch || item;
                }
            });

            const showSearchBubbleForEmptyResult = Boolean(options.showSearchBubble && searchTerm && visibleCount === 0);

            if (noResultsMessage) {
                noResultsMessage.textContent = searchTerm
                    ? `The position "${searchInput.value.trim()}" is not available right now.`
                    : 'No jobs matched your filters.';
                noResultsMessage.classList.toggle('d-none', visibleCount > 0 || showSearchBubbleForEmptyResult || options.suppressNoResults);
            }

            if (showSearchBubbleForEmptyResult) {
                showSearchBubble(`The position "${searchInput.value.trim()}" is not available right now.`);
            } else if (visibleCount > 0 || !searchTerm || options.hideSearchBubble) {
                hideSearchBubble();
            }

            if (options.focusFirstMatch && firstMatch) {
                firstMatch.classList.add('is-search-focused');
                firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            return { visibleCount, firstMatch };
        }

        function updateSelectOptions(select, values, defaultLabel) {
            if (!select) {
                return;
            }

            const previousValue = select.value;
            select.innerHTML = '';
            select.appendChild(new Option(defaultLabel, ''));

            (values || []).forEach((value) => {
                select.appendChild(new Option(value, value));
            });

            select.value = Array.from(select.options).some((option) => option.value === previousValue)
                ? previousValue
                : '';
        }

        function hydrateUpdatedVacancies(payload) {
            if (!payload || !jobList || typeof payload.html !== 'string') {
                return;
            }

            const normalizeHtml = (html) => html.replace(/\s+/g, ' ').trim();
            if (normalizeHtml(jobList.innerHTML) === normalizeHtml(payload.html)) {
                return;
            }

            jobList.innerHTML = payload.html;
            if (jobOpportunitiesCount) {
                const count = Number(payload.count || 0);
                jobOpportunitiesCount.textContent = `${count} ${count === 1 ? 'opportunity' : 'opportunities'} available`;
            }
            if (jobSeeMoreWrap) {
                jobSeeMoreWrap.classList.toggle('d-none', !payload.hasMore);
            }
            if (jobSeeMoreLink && payload.seeMoreUrl) {
                jobSeeMoreLink.href = payload.seeMoreUrl;
            }

            updateSelectOptions(departmentFilter, payload.departments, 'All Departments');
            updateSelectOptions(employmentFilter, payload.employments, 'All Types');
            updateSelectOptions(locationFilter, payload.locations, 'All Location');

            page.querySelectorAll('#jobList > .job-item').forEach((item) => {
                item.classList.add('guest-index-reveal', 'is-scroll-animated');
            });
            page.querySelectorAll('#jobList .job-card').forEach((item) => {
                item.classList.add('guest-index-card-motion');
            });
            page.querySelectorAll('#jobList .badge').forEach((item, index) => {
                item.classList.add('guest-index-pop');
                item.style.setProperty('--guest-index-delay', `${120 + ((index % 4) * 40)}ms`);
            });

            applyFilters({ suppressNoResults: true, hideSearchBubble: true });
        }

        jobSearchForm?.addEventListener('submit', function (event) {
            event.preventDefault();
            applyFilters({ focusFirstMatch: true, showSearchBubble: true });
        });

        searchInput?.addEventListener('input', function () {
            applyFilters({ suppressNoResults: true, hideSearchBubble: true });
        });
        departmentFilter?.addEventListener('change', applyFilters);
        departmentFilter?.addEventListener('blur', collapseDepartmentOptions);
        employmentFilter?.addEventListener('change', applyFilters);
        locationFilter?.addEventListener('change', applyFilters);

        openPositionsMetric?.addEventListener('click', function () {
            jobVacanciesSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        departmentsMetric?.addEventListener('click', function () {
            departmentFilter?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(expandDepartmentOptions, 450);
        });

        const vacancyCheckUrl = @json(route('guest.jobVacancies.check'));
        let currentVacancySignature = @json($vacancySignature ?? '');
        const vacancySignatureStorageKey = 'guest_job_vacancy_signature_v1';
        const storedVacancySignature = window.sessionStorage?.getItem(vacancySignatureStorageKey) || '';
        if (storedVacancySignature && storedVacancySignature === currentVacancySignature) {
            currentVacancySignature = storedVacancySignature;
        } else if (currentVacancySignature) {
            window.sessionStorage?.setItem(vacancySignatureStorageKey, currentVacancySignature);
        }
        let isCheckingVacancies = false;

        async function refreshWhenVacanciesChange() {
            if (!vacancyCheckUrl || document.hidden || isCheckingVacancies) {
                return;
            }

            isCheckingVacancies = true;

            try {
                const url = new URL(vacancyCheckUrl, window.location.origin);
                if (currentVacancySignature) {
                    url.searchParams.set('signature', currentVacancySignature);
                }

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                    },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const nextSignature = (payload.signature || '').toString();
                const lastAppliedSignature = window.sessionStorage?.getItem(vacancySignatureStorageKey) || currentVacancySignature;
                const hasAdminPositionUpdate = Boolean(
                    payload.changed === true &&
                    typeof payload.html === 'string' &&
                    nextSignature &&
                    nextSignature !== currentVacancySignature &&
                    nextSignature !== lastAppliedSignature
                );

                if (!hasAdminPositionUpdate) {
                    currentVacancySignature = nextSignature || currentVacancySignature;
                    if (currentVacancySignature) {
                        window.sessionStorage?.setItem(vacancySignatureStorageKey, currentVacancySignature);
                    }
                    return;
                }

                if (hasAdminPositionUpdate) {
                    hydrateUpdatedVacancies(payload);
                }

                currentVacancySignature = nextSignature || currentVacancySignature;
                if (currentVacancySignature) {
                    window.sessionStorage?.setItem(vacancySignatureStorageKey, currentVacancySignature);
                }
            } catch (error) {
                // Keep the page quiet if the background check cannot reach the server.
            } finally {
                isCheckingVacancies = false;
            }
        }

        window.addEventListener('focus', refreshWhenVacanciesChange);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshWhenVacanciesChange();
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chatbotRoot = document.getElementById('ncChatbot');
        if (!chatbotRoot) return;

        const endpoint = chatbotRoot.dataset.endpoint;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const launcher = document.getElementById('ncChatLauncher');
        const panel = document.getElementById('ncChatPanel');
        const closeBtn = document.getElementById('ncChatClose');
        const messagesEl = document.getElementById('ncChatMessages');
        const chipsEl = document.getElementById('ncChatChips');
        const form = document.getElementById('ncChatForm');
        const input = document.getElementById('ncChatInput');
        const sendBtn = document.getElementById('ncChatSend');
        const helpHint = document.getElementById('ncChatHelpHint');
        const robotHead = chatbotRoot.querySelector('.nc-robot');
        const robotEyes = Array.from(chatbotRoot.querySelectorAll('.nc-robot-eye'));
        const historyKey = 'nc_guest_chat_history_v1';
        let isSending = false;
        let hintHideTimeout = null;
        let hintCycleInterval = null;
        let followRaf = null;
        let followX = null;
        let followY = null;
        let sadTimeout = null;
        let rollReturnTimeout = null;
        let dizzyReturnTimeout = null;

        function escapeHtml(text) {
            return (text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function saveHistory() {
            const bubbles = Array.from(messagesEl.querySelectorAll('.nc-bubble')).map((bubble) => ({
                role: bubble.classList.contains('user') ? 'user' : 'bot',
                text: bubble.textContent || '',
            }));
            localStorage.setItem(historyKey, JSON.stringify(bubbles.slice(-30)));
        }

        function appendMessage(role, text, shouldSave = true) {
            const bubble = document.createElement('div');
            bubble.className = 'nc-bubble ' + role;
            bubble.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');
            messagesEl.appendChild(bubble);
            messagesEl.scrollTop = messagesEl.scrollHeight;
            if (shouldSave) saveHistory();
        }

        function setTyping(visible) {
            const existing = document.getElementById('ncTyping');
            if (!visible) {
                if (existing) existing.remove();
                return;
            }
            if (existing) return;

            const bubble = document.createElement('div');
            bubble.id = 'ncTyping';
            bubble.className = 'nc-bubble bot';
            bubble.innerHTML = '<span class="nc-typing"><i></i><i></i><i></i></span>';
            messagesEl.appendChild(bubble);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function setQuickChips(items) {
            const list = Array.isArray(items) && items.length ? items.slice(0, 6) : [
                'Explain this website',
                'Show available jobs',
                'How to apply',
                'Application requirements',
                'How to create an account',
                'Where are policy pages?',
            ];

            chipsEl.innerHTML = '';
            list.forEach((label) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'nc-chatbot-chip';
                button.dataset.msg = label;
                button.textContent = label;
                chipsEl.appendChild(button);
            });
        }

        function showHint(text, hideAfterMs = null) {
            if (!helpHint || !panel.hidden) return;

            helpHint.textContent = text;
            helpHint.classList.add('is-visible');

            if (hintHideTimeout) {
                clearTimeout(hintHideTimeout);
                hintHideTimeout = null;
            }

            if (typeof hideAfterMs === 'number' && hideAfterMs > 0) {
                hintHideTimeout = setTimeout(function () {
                    helpHint.classList.remove('is-visible');
                    hintHideTimeout = null;
                }, hideAfterMs);
            }
        }

        function showHelpHintWindow() {
            showHint('Click me if you need help', 5000);
        }

        function startHelpHintCycle() {
            if (!helpHint) return;
            showHelpHintWindow();
            hintCycleInterval = setInterval(showHelpHintWindow, 40000);
        }

        function applyRobotFollow(clientX, clientY) {
            if (!robotHead) return;

            const rect = launcher.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const dx = clientX - centerX;
            const dy = clientY - centerY;
            const distance = Math.sqrt((dx * dx) + (dy * dy)) || 1;

            // Head follows cursor subtly, but launcher stays in place.
            const maxShift = 4;
            const maxTilt = 8;
            const shiftX = Math.max(-maxShift, Math.min(maxShift, (dx / distance) * maxShift));
            const shiftY = Math.max(-maxShift, Math.min(maxShift, (dy / distance) * maxShift));
            const tilt = Math.max(-maxTilt, Math.min(maxTilt, dx / 18));
            robotHead.style.transform = `translate(${shiftX}px, ${shiftY}px) rotate(${tilt}deg)`;

            const eyeMax = 4.2;
            const eyeX = Math.max(-eyeMax, Math.min(eyeMax, (dx / distance) * eyeMax));
            const eyeY = Math.max(-eyeMax, Math.min(eyeMax, (dy / distance) * eyeMax));
            robotEyes.forEach((eye) => {
                eye.style.setProperty('--eye-x', `${eyeX}px`);
                eye.style.setProperty('--eye-y', `${eyeY}px`);
            });
        }

        function queueRobotFollow(clientX, clientY) {
            followX = clientX;
            followY = clientY;
            if (followRaf) return;
            followRaf = requestAnimationFrame(function () {
                applyRobotFollow(followX, followY);
                followRaf = null;
            });
        }

        function resetRobotFollow() {
            if (!robotHead) return;
            robotHead.style.transform = 'translate(0px, 0px) rotate(0deg)';
            robotEyes.forEach((eye) => {
                eye.style.setProperty('--eye-x', '0px');
                eye.style.setProperty('--eye-y', '0px');
            });
        }

        function clearSadFace() {
            if (!robotHead) return;
            if (sadTimeout) {
                clearTimeout(sadTimeout);
                sadTimeout = null;
            }
            robotHead.classList.remove('is-sad');
        }

        function showSadFaceForTwoSeconds() {
            if (!robotHead) return;
            clearSadFace();
            robotHead.classList.add('is-sad');
            sadTimeout = setTimeout(function () {
                robotHead.classList.remove('is-sad');
                sadTimeout = null;
            }, 1000);
        }

        function loadHistory() {
            try {
                const parsed = JSON.parse(localStorage.getItem(historyKey) || '[]');
                if (!Array.isArray(parsed) || parsed.length === 0) {
                    appendMessage('bot', 'Hi. I am your NC Career Assistant. Ask me about jobs, application steps, requirements, or policy pages.', false);
                    saveHistory();
                    return;
                }
                parsed.forEach((item) => {
                    if (!item || !item.role || !item.text) return;
                    appendMessage(item.role === 'user' ? 'user' : 'bot', String(item.text), false);
                });
                saveHistory();
            } catch (error) {
                appendMessage('bot', 'Hi. I am your NC Career Assistant. Ask me about jobs, application steps, requirements, or policy pages.', true);
            }
        }

        async function sendMessage(messageText) {
            const message = (messageText || '').trim();
            if (!message || isSending) return;

            isSending = true;
            sendBtn.disabled = true;
            input.disabled = true;
            appendMessage('user', message);
            input.value = '';
            input.style.height = 'auto';
            setTyping(true);

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message }),
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const data = await response.json();
                setTyping(false);
                appendMessage('bot', data.reply || 'I can help with jobs, requirements, and policies.');
                setQuickChips(data.suggestions || []);
            } catch (error) {
                setTyping(false);
                appendMessage('bot', 'I could not connect right now. Please try again in a few seconds.');
            } finally {
                isSending = false;
                sendBtn.disabled = false;
                input.disabled = false;
                input.focus();
            }
        }

        function openPanel() {
            panel.hidden = false;
            panel.classList.remove('pop-in');
            void panel.offsetWidth;
            panel.classList.add('pop-in');
            if (rollReturnTimeout) clearTimeout(rollReturnTimeout);
            if (dizzyReturnTimeout) clearTimeout(dizzyReturnTimeout);
            chatbotRoot.classList.remove('rolling-back', 'dizzy');
            chatbotRoot.classList.add('is-open');
            launcher.setAttribute('aria-expanded', 'true');
            if (helpHint) helpHint.classList.remove('is-visible');
            input.focus();
        }

        function closePanel() {
            panel.hidden = true;
            panel.classList.remove('pop-in');
            chatbotRoot.classList.remove('is-open');
            if (rollReturnTimeout) clearTimeout(rollReturnTimeout);
            if (dizzyReturnTimeout) clearTimeout(dizzyReturnTimeout);
            chatbotRoot.classList.remove('rolling-back', 'dizzy');
            void chatbotRoot.offsetWidth;
            chatbotRoot.classList.add('rolling-back');
            rollReturnTimeout = setTimeout(function () {
                chatbotRoot.classList.remove('rolling-back');
                chatbotRoot.classList.add('dizzy');
                dizzyReturnTimeout = setTimeout(function () {
                    chatbotRoot.classList.remove('dizzy');
                    showHint("I'm fine", 1400);
                }, 900);
            }, 1050);
            launcher.setAttribute('aria-expanded', 'false');
        }

        launcher.addEventListener('click', function () {
            if (panel.hidden) {
                openPanel();
            } else {
                closePanel();
            }
        });

        closeBtn.addEventListener('click', closePanel);

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage(input.value);
        });

        input.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 128) + 'px';
        });

        chipsEl.addEventListener('click', function (event) {
            const target = event.target.closest('.nc-chatbot-chip');
            if (!target) return;
            sendMessage(target.dataset.msg || target.textContent || '');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) {
                closePanel();
            }
        });

        if (robotHead && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.addEventListener('mousemove', function (event) {
                queueRobotFollow(event.clientX, event.clientY);
            });

            launcher.addEventListener('mouseenter', function () {
                clearSadFace();
                showHint('Need help?');
            });
            launcher.addEventListener('mouseleave', function () {
                resetRobotFollow();
                showSadFaceForTwoSeconds();
                showHint('Ohh.. okay', 1800);
            });
            window.addEventListener('blur', resetRobotFollow);
        }

        loadHistory();
        startHelpHintCycle();
    });
</script>

@if(session('popup_error'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('applicationPopupModal');
        if (!modalEl) return;
        const popup = new bootstrap.Modal(modalEl);
        popup.show();
    });
</script>
@endif

@if(session('show_rating_modal') || session('success') === 'Submitted successfully')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nextModalEl = document.getElementById('applicationNextModal');
        const ratingModalEl = document.getElementById('applicationRatingModal');
        if (!nextModalEl || !ratingModalEl) return;

        const nextPopup = new bootstrap.Modal(nextModalEl);
        const ratingPopup = new bootstrap.Modal(ratingModalEl);
        nextPopup.show();

        document.getElementById('continueToRatingBtn')?.addEventListener('click', function () {
            nextPopup.hide();
            ratingPopup.show();
        });
    });
</script>
@endif



</main>
@endsection





