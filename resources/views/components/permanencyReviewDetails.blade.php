<div class="space-y-3 text-sm">
    <p class="font-semibold text-emerald-800">{{ ucwords(str_replace('_', ' ', $review->recommendation)) }} · {{ $review->overall_rating }}/5</p>
    <p class="text-slate-500">Submitted {{ $review->created_at->format('M j, Y') }} · Permanency date {{ $review->due_date->format('M j, Y') }}</p>
    <p class="font-semibold text-slate-800">{{ $review->template->title }}</p>
    <dl class="divide-y divide-slate-100">
        @foreach ($review->template->criteria as $index => $criterion)
            <div class="flex justify-between gap-4 py-2"><dt>{{ $criterion['label'] }}</dt><dd class="font-semibold">{{ $review->ratings[$index] }}/5</dd></div>
        @endforeach
    </dl>
    <p class="whitespace-pre-wrap rounded-xl bg-slate-50 p-4 text-slate-700">{{ $review->comments }}</p>
</div>
