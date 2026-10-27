@php
    $scheduledInterview = $applicant->relationLoaded('interviews')
        ? $applicant->interviews->first(fn ($interview) => !$interview->ended_at && $interview->date && $interview->time
            && strcasecmp(trim($interview->interview_type), trim($applicant->application_status)) === 0)
        : null;
@endphp
@if($scheduledInterview)
    <section class="interview-invitation mt-3" aria-label="Your interview schedule">
        <div class="interview-invitation-date" aria-hidden="true">
            <span>{{ $scheduledInterview->date->format('M') }}</span>
            <strong>{{ $scheduledInterview->date->format('d') }}</strong>
            <span>{{ $scheduledInterview->date->format('D') }}</span>
        </div>
        <div class="interview-invitation-content">
            <div class="interview-invitation-kicker"><i class="bi bi-calendar-check" aria-hidden="true"></i> YOUR INTERVIEW SCHEDULE</div>
            <h3>{{ $scheduledInterview->interview_type }}</h3>
            <div class="interview-invitation-details">
                <span><i class="bi bi-calendar3" aria-hidden="true"></i> {{ $scheduledInterview->date->format('l, F j, Y') }}</span>
                <span><i class="bi bi-clock" aria-hidden="true"></i> {{ \Carbon\Carbon::parse($scheduledInterview->time)->format('g:i A') }} <small>({{ config('app.timezone') }})</small></span>
            </div>
            <p>Please be ready a few minutes before your scheduled time. Check your email for the interview instructions.</p>
        </div>
        <span class="interview-invitation-badge"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Scheduled</span>
    </section>
@endif
