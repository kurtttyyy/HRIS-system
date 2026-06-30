@php
    $idleTabSession = trim((string) (
        request()->query('tab_session')
        ?? request()->attributes->get('tab_session', '')
    ));
@endphp

@auth
<div
    id="idle-session-warning"
    class="idle-session-warning"
    role="dialog"
    aria-modal="true"
    aria-labelledby="idle-session-warning-title"
    aria-describedby="idle-session-warning-description"
    aria-hidden="true"
>
    <div class="idle-session-warning__backdrop"></div>
    <div class="idle-session-warning__card">
        <div class="idle-session-warning__mascot" aria-hidden="true">
            <img
                src="{{ asset('images/idle-session-mascot.gif') }}"
                alt=""
                class="idle-session-warning__mascot-image"
            >
        </div>

        <p class="idle-session-warning__eyebrow">Are you still there?</p>
        <h2 id="idle-session-warning-title">Your session will end soon</h2>
        <p id="idle-session-warning-description">
            For your account’s safety, you will be signed out automatically unless you continue using the system.
        </p>

        <div class="idle-session-warning__timer" aria-live="polite">
            <svg viewBox="0 0 120 120" aria-hidden="true">
                <circle class="idle-session-warning__timer-track" cx="60" cy="60" r="52"></circle>
                <circle id="idle-session-warning-progress" class="idle-session-warning__timer-progress" cx="60" cy="60" r="52"></circle>
            </svg>
            <div>
                <strong id="idle-session-warning-count">50</strong>
                <span>seconds</span>
            </div>
        </div>

        <button id="idle-session-continue" type="button">
            Continue
        </button>
    </div>
</div>

<form id="idle-session-logout-form" method="POST" action="{{ route('logout') }}" hidden>
    @csrf
    @if ($idleTabSession !== '')
        <input type="hidden" name="tab_session" value="{{ $idleTabSession }}">
    @endif
</form>

<style>
    .idle-session-warning {
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }

    .idle-session-warning.is-visible {
        display: flex;
    }

    .idle-session-warning__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(2, 6, 23, 0.76);
        backdrop-filter: blur(9px);
    }

    .idle-session-warning__card {
        position: relative;
        width: min(100%, 31rem);
        padding: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.78);
        border-radius: 2rem;
        background: #fff;
        color: #0f172a;
        text-align: center;
        box-shadow: 0 32px 90px rgba(2, 6, 23, 0.42);
        animation: idle-session-card-in 320ms cubic-bezier(0.22, 0.9, 0.2, 1) both;
    }

    .idle-session-warning__mascot {
        position: relative;
        width: 10rem;
        height: 7.5rem;
        margin: -0.35rem auto 0.75rem;
        filter: drop-shadow(0 12px 14px rgba(15, 23, 42, 0.16));
    }

    .idle-session-warning__mascot-image {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .idle-session-warning__eyebrow {
        margin: 0 0 0.4rem;
        color: #15803d;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .idle-session-warning__card h2 {
        margin: 0;
        font-size: clamp(1.55rem, 4vw, 2.15rem);
        font-weight: 900;
        line-height: 1.12;
    }

    .idle-session-warning__card > p:not(.idle-session-warning__eyebrow):not(.idle-session-warning__hint) {
        margin: 0.8rem auto 0;
        max-width: 26rem;
        color: #475569;
        font-size: 1rem;
        line-height: 1.55;
    }

    .idle-session-warning__timer {
        position: relative;
        width: 9.5rem;
        height: 9.5rem;
        margin: 1.35rem auto;
    }

    .idle-session-warning__timer svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }

    .idle-session-warning__timer circle {
        fill: none;
        stroke-width: 9;
    }

    .idle-session-warning__timer-track {
        stroke: #e2e8f0;
    }

    .idle-session-warning__timer-progress {
        stroke: #16a34a;
        stroke-linecap: round;
        transition: stroke-dashoffset 250ms linear, stroke 250ms ease;
    }

    .idle-session-warning__timer > div {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .idle-session-warning__timer strong {
        font-size: 2.7rem;
        font-weight: 900;
        line-height: 1;
    }

    .idle-session-warning__timer span {
        margin-top: 0.25rem;
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    #idle-session-continue {
        width: 100%;
        min-height: 3.7rem;
        border: 0;
        border-radius: 1rem;
        background: #15803d;
        color: #fff;
        cursor: pointer;
        font-size: 1.08rem;
        font-weight: 900;
        box-shadow: 0 12px 26px rgba(21, 128, 61, 0.24);
        transition: background 160ms ease, transform 160ms ease;
    }

    #idle-session-continue:hover,
    #idle-session-continue:focus-visible {
        background: #166534;
        outline: 4px solid rgba(74, 222, 128, 0.42);
        transform: translateY(-1px);
    }

    .idle-session-warning__hint {
        margin: 0.8rem 0 0;
        color: #64748b;
        font-size: 0.78rem;
    }

    @keyframes idle-session-card-in {
        from { opacity: 0; transform: translateY(18px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @media (max-width: 520px) {
        .idle-session-warning__card {
            padding: 1.5rem;
            border-radius: 1.5rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .idle-session-warning__card {
            animation: none;
        }
    }
</style>

<script>
    (() => {
        if (window.__idleSessionTimeoutStarted) return;
        window.__idleSessionTimeoutStarted = true;

        const inactivityMilliseconds = 3 * 60 * 1000;
        const warningSeconds = 50;
        const circumference = 2 * Math.PI * 52;
        const warning = document.getElementById('idle-session-warning');
        const continueButton = document.getElementById('idle-session-continue');
        const count = document.getElementById('idle-session-warning-count');
        const progress = document.getElementById('idle-session-warning-progress');
        const logoutForm = document.getElementById('idle-session-logout-form');
        const csrfTokenUrl = @json(route('csrf.token'));

        if (!warning || !continueButton || !count || !progress || !logoutForm) return;

        let lastActivityAt = Date.now();
        let warningDeadline = 0;
        let warningVisible = false;
        let logoutStarted = false;
        let lastPointerActivityAt = 0;
        let previousBodyOverflow = '';

        progress.style.strokeDasharray = `${circumference}`;
        progress.style.strokeDashoffset = '0';

        const recordActivity = (event) => {
            if (warningVisible || logoutStarted) return;

            if (event?.type === 'pointermove') {
                const now = Date.now();
                if (now - lastPointerActivityAt < 1000) return;
                lastPointerActivityAt = now;
            }

            lastActivityAt = Date.now();
        };

        const showWarning = () => {
            if (warningVisible || logoutStarted) return;
            warningVisible = true;
            warningDeadline = Date.now() + (warningSeconds * 1000);
            warning.classList.add('is-visible');
            warning.setAttribute('aria-hidden', 'false');
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            continueButton.focus();
        };

        const hideWarning = () => {
            warningVisible = false;
            warningDeadline = 0;
            warning.classList.remove('is-visible');
            warning.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = previousBodyOverflow;
            lastActivityAt = Date.now();
        };

        const logout = async () => {
            if (logoutStarted) return;
            logoutStarted = true;
            count.textContent = '0';
            continueButton.disabled = true;
            continueButton.textContent = 'Signing out…';

            try {
                const response = await fetch(`${csrfTokenUrl}?_=${Date.now()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                const data = response.ok ? await response.json() : {};
                const token = typeof data.token === 'string' ? data.token : '';
                const tokenInput = logoutForm.querySelector('input[name="_token"]');
                if (token && tokenInput) tokenInput.value = token;
            } catch (error) {
                console.warn('Unable to refresh logout token.', error);
            }

            logoutForm.submit();
        };

        const update = () => {
            const now = Date.now();
            if (!warningVisible) {
                if (now - lastActivityAt >= inactivityMilliseconds) showWarning();
                return;
            }

            const millisecondsRemaining = Math.max(0, warningDeadline - now);
            const secondsRemaining = Math.ceil(millisecondsRemaining / 1000);
            const elapsedRatio = 1 - (millisecondsRemaining / (warningSeconds * 1000));
            count.textContent = `${secondsRemaining}`;
            progress.style.strokeDashoffset = `${circumference * Math.min(Math.max(elapsedRatio, 0), 1)}`;
            progress.style.stroke = secondsRemaining <= 10 ? '#dc2626' : '#16a34a';

            if (millisecondsRemaining <= 0) logout();
        };

        ['pointerdown', 'pointermove', 'keydown', 'scroll', 'touchstart', 'wheel'].forEach((eventName) => {
            window.addEventListener(eventName, recordActivity, { passive: true });
        });

        continueButton.addEventListener('click', hideWarning);
        window.setInterval(update, 250);
    })();
</script>
@endauth
