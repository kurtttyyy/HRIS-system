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
                                            <div class="small text-muted">{{ $degree->school_name ?: 'School N/A' }}{{ $degree->year_finished ? ' - '.$degree->year_finished : '' }}</div>
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
