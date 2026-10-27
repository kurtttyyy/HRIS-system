@if($applicant->application_status === 'Hired')
    <section class="interview-invitation mt-3" aria-label="Your reporting date">
        @if($applicant->date_hired)
            <div class="interview-invitation-date" aria-hidden="true">
                <span>{{ $applicant->date_hired->format('M') }}</span>
                <strong>{{ $applicant->date_hired->format('d') }}</strong>
                <span>{{ $applicant->date_hired->format('D') }}</span>
            </div>
        @endif
        <div class="interview-invitation-content">
            <div class="interview-invitation-kicker"><i class="bi bi-briefcase" aria-hidden="true"></i> YOUR FIRST DAY</div>
            <h3>You're hired! Welcome to the team.</h3>
            @if($applicant->date_hired)
                <div class="interview-invitation-details">
                    <span><i class="bi bi-calendar-check" aria-hidden="true"></i> Report on {{ $applicant->date_hired->format('l, F j, Y') }}</span>
                </div>
                <p>This is your reporting / start date set by HR. Please contact HR for your reporting time, location, and first-day instructions.</p>
            @else
                <p>Your reporting date has not been set yet. Please wait for HR to confirm your start date.</p>
            @endif
        </div>
        <span class="interview-invitation-badge"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Hired</span>
    </section>
@endif
