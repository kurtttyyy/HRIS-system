<header class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
    <div class="container-fluid">
        <div class="navbar-brand d-flex align-items-center gap-3">
            <img src="{{ asset('images/logo.webp') }}" alt="Logo" height="70">
            <div class="d-flex flex-column">
                <span class="fw-bold fs-2 mb-0 text-white">HUMAN RESOURCES DEPARTMENT</span>
                <small class="subtext">Join Our Team</small>
            </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-4">
            <!-- HOME -->
            <a href="{{ route('guest.index') }}" class="btn btn-outline-light border-0 nav-home-link">Home</a>

            <a href="{{ route('guest.about') }}" class="btn btn-outline-light border-0 nav-home-link">About</a>

            <!-- Buttons -->
            <a href="{{ route('guest.jobOpenLanding') }}" class=" btn btn-outline-light border-0 nav-home-link">Job Vacancies</a>

            <!-- Application Status button triggers modal -->
            <button id="applicationStatusBtn"
                    class="btn btn-outline-light border-0 nav-home-link">
                Application Status
            </button>

        </div>
    </div>
</header>

<div class="header-divider" aria-hidden="true"></div>

{{-- Email Verification Modal --}}
{{-- Email Verification Modal --}}
<div class="modal fade" id="emailCheckModal" tabindex="-1" aria-labelledby="emailCheckLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 justify-content-center position-relative">
                <div class="text-center w-100">
                    <i class="bi bi-envelope-check text-primary display-4 mb-2"></i>
                    <h5 class="modal-title fw-bold" id="emailCheckLabel">Track Application</h5>
                    <p class="text-muted small">Enter your tracking number</p>
                </div>

                <!-- X Close Button -->
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('guest.application.submit') }}" method="POST">
                @csrf
                <div class="modal-body px-4">
                    <input type="text" id="verifyEmail" name="application_lookup" class="form-control py-2 rounded-pill shadow-sm" placeholder="APP-YYYYMMDD-XXXXXX">
                    <div id="emailError" class="text-danger mt-2 text-center small" style="display:none;">Please enter your tracking number.</div>
                </div>

                <div class="modal-footer border-0 justify-content-center pb-4">
                    <!-- Continue Button -->
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-bold">
                        Continue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const appStatusBtn = document.getElementById('applicationStatusBtn');

        appStatusBtn.addEventListener('click', function() {
            const emailModal = new bootstrap.Modal(document.getElementById('emailCheckModal'));
            emailModal.show();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('header.navbar');
    setTimeout(() => {
        header.classList.add('loaded');
    }, 100); // small delay for smooth effect
});


</script>
