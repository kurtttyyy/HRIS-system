<div class="space-y-3 text-sm">
    <p class="font-semibold text-emerald-800">{{ ['recommend_permanent' => 'Recommend permanent appointment', 'extend_probation' => 'Recommend extending probation', 'not_recommended' => 'Do not recommend permanent appointment'][$review->recommendation] ?? ucwords(str_replace('_', ' ', $review->recommendation)) }} <span class="ml-2 inline-flex rounded-full bg-emerald-50 px-3 py-1">Overall rating: {{ $review->overall_rating }}/5</span></p>
    <p class="text-slate-500">Submitted {{ $review->created_at->format('M j, Y') }} | Review date {{ $review->due_date->format('M j, Y') }}</p>
    @if($showRatingBreakdown ?? true)
    <p class="font-semibold text-slate-800">{{ $review->template->title }}</p>
    <dl class="divide-y divide-slate-100">
        @foreach ($review->template->criteria as $index => $criterion)
            <div class="flex justify-between gap-4 py-2"><dt>{{ $criterion['label'] }}</dt><dd class="font-semibold">{{ $review->ratings[$index] }}/5</dd></div>
        @endforeach
    </dl>
    @endif
    <div class="rounded-xl bg-slate-50 p-4 text-slate-700"><p class="mb-2 font-semibold">Comments</p><p class="whitespace-pre-wrap">{{ filled($review->comments) ? $review->comments : 'No comments provided.' }}</p></div>
</div>
