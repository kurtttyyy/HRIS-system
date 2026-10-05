<div class="overflow-x-auto">
    <table class="w-full min-w-[540px] table-fixed border-collapse border border-slate-300 bg-white text-sm">
        <caption class="sr-only">Evaluation questions. Choose one rating per row, from 5 (Excellent) to 1 (Poor).</caption>
        <colgroup><col style="width: 65%">@foreach (range(5, 1) as $score)<col style="width: 7%">@endforeach</colgroup>
        <thead>
            <tr class="bg-slate-50">
                <th scope="col" class="border border-slate-300 px-3 py-2 text-right font-semibold">Rating</th>
                @foreach (range(5, 1) as $score)
                    <th scope="col" class="border border-slate-300 py-2 text-center font-semibold">{{ $score }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($ratingCriteria as $index => $criterion)
                @include('components.evaluationRating', [
                    'selectedRating' => $ratingReview ? ($ratingReview->ratings[$index] ?? null) : ($retry ? old('ratings.'.$index) : null),
                    'ratingDisabled' => (bool) $ratingReview,
                ])
            @endforeach
        </tbody>
    </table>
    <p class="mt-2 text-xs text-slate-500">5 = Excellent; 4 = Very good; 3 = Satisfactory; 2 = Needs improvement; 1 = Poor.</p>
</div>
