@php
  $serviceYear = static function ($value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
      return '';
    }
    if (strcasecmp($value, 'Present') === 0) {
      return 'Present';
    }
    try {
      return preg_match('/^\d{4}$/', $value)
        ? $value
        : \Carbon\Carbon::parse($value)->format('Y');
    } catch (\Throwable $e) {
      return '';
    }
  };
  $storedServiceRows = $experienceUser->employee?->service_record_rows ?? [];
  $serviceRows = collect(is_array($storedServiceRows) ? $storedServiceRows : []);
  if ($serviceRows->isEmpty()) {
    // Unsaved service records display position history plus a current snapshot.
    $serviceRows = collect($experienceUser->positionHistories ?? [])
      ->map(fn ($history) => [
        'designation' => $history->old_position ?? '',
        'from_date' => '',
        'to_date' => $history->changed_at ?? $history->created_at,
      ])
      ->filter(fn ($row) => trim((string) $row['designation']) !== '')
      ->values();
    $serviceRows->push([
      'designation' => trim((string) ($experienceUser->job_role ?? '')) === 'President'
        ? 'President'
        : ($experienceUser->employee?->position ?? $experienceUser->position ?? $experienceUser->applicant?->work_position ?? ''),
      'from_date' => $experienceUser->applicant?->date_hired ?? $experienceUser->employee?->employement_date,
      'to_date' => now()->format('Y-m-d'),
    ]);
  }
  $serviceExperiences = $serviceRows
    ->filter(fn ($row) => is_array($row))
    ->map(function ($row) use ($serviceYear) {
      $from = $serviceYear($row['from_date'] ?? null);
      $to = $serviceYear($row['to_date'] ?? null);
      return [
        'position' => trim((string) ($row['designation'] ?? '')),
        'years' => $from !== '' && $to !== '' ? $from.'-'.$to : ($from !== '' ? $from : $to),
      ];
    })
    ->filter(fn ($row) => $row['position'] !== '' || $row['years'] !== '')
    ->reverse()
    ->values();
  if ($serviceExperiences->isEmpty()) {
    $fallbackDuration = trim((string) $displayWorkDuration);
    if (in_array($fallbackDuration, ['-', '(-)', '()', '( )', 'N/A'], true)) {
      $fallbackDuration = '';
    }
    $serviceExperiences = $experienceLines->map(fn ($line) => [
      'position' => $line,
      'years' => $fallbackDuration,
    ]);
  }
@endphp
@if ($serviceExperiences->isNotEmpty())
  <div class="space-y-1">
    @foreach ($serviceExperiences as $experience)
      @if ($experience['position'] !== '')
        <div>&bull; {{ $experience['position'] }}</div>
      @endif
      @if ($experience['years'] !== '')
        <div class="pl-4 text-stone-700">({{ $experience['years'] }})</div>
      @endif
    @endforeach
  </div>
@else
  -
@endif
