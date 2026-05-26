@foreach ($open_position as $position)
    <div class="col-12 col-md-6 job-item"
        data-title="{{ \Illuminate\Support\Str::lower($position->title) }}"
        data-department="{{ \Illuminate\Support\Str::lower($position->department) }}"
        data-employment="{{ \Illuminate\Support\Str::lower($position->employment) }}"
        data-location="{{ \Illuminate\Support\Str::lower($position->location) }}"
        data-description="{{ \Illuminate\Support\Str::lower($position->job_description) }}"
    >
        <div class="job-card card animated-card delay-5 hover-card border-1">
            <div class="job-card-top">
                <div>
                    <h5 class="job-card-title">{{ $position->title }}</h5>
                    <div class="job-card-dept">{{ $position->department }}</div>
                </div>
                @php
                    $postedDays = $position->created_at
                        ? now()->diffInDays($position->created_at, true)
                        : null;
                    $postedDaysWhole = is_null($postedDays) ? null : (int) floor($postedDays);
                @endphp
                @if (!is_null($postedDaysWhole) && $postedDaysWhole <= 3)
                    <span class="badge bg-success">New</span>
                @elseif (!is_null($postedDaysWhole))
                    <span class="badge bg-secondary">{{ $postedDaysWhole }} {{ $postedDaysWhole === 1 ? 'day' : 'days' }} ago</span>
                @endif
            </div>

            @php
                $lines = preg_split("/\r\n|\n|\r/", $position->job_description);
            @endphp

            <div class="job-meta-row">
                <span class="job-meta-pill">{{ $position->location }}</span>
                <span class="job-meta-pill">{{ $position->employment }}</span>
                <span class="job-meta-pill">{{ $position->work_mode }}</span>
            </div>

            <ul class="job-card-copy">
                @foreach (array_slice($lines, 0, 3) as $line)
                    @php
                        $cleanLine = preg_replace('/^[^\pL\pN]+/u', '', (string) $line);
                        $cleanLine = trim((string) preg_replace('/\s+/', ' ', $cleanLine));
                    @endphp
                    @if ($cleanLine !== '')
                        <li>{{ \Illuminate\Support\Str::limit($cleanLine, 150, '...') }}</li>
                    @endif
                @endforeach
            </ul>

            <button
                onclick="window.location.href='{{ route('guest.jobOpen', $position->id) }}';"
                class="btn btn-primary w-100 green-btn"
            >
                View Details & Apply
            </button>
        </div>
    </div>
@endforeach
