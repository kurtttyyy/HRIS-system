<tr>
    <th scope="row" class="border border-slate-300 p-3 text-left align-middle font-normal">
        <span class="block font-semibold">{{ $index + 1 }}. {{ $criterion['label'] }}</span>
        <span class="mt-1 block text-sm text-slate-600">{{ $criterion['description'] ?? '' }}</span>
    </th>
    @foreach ([5 => 'Excellent', 4 => 'Very good', 3 => 'Satisfactory', 2 => 'Needs improvement', 1 => 'Poor'] as $score => $label)
        <td class="border border-slate-300 p-0 text-center align-middle has-[:checked]:bg-emerald-50">
            <label class="flex min-h-16 cursor-pointer items-center justify-center p-3">
                <input type="radio" name="ratings[{{ $index }}]" value="{{ $score }}" required
                    aria-label="{{ $criterion['label'] }}: {{ $score }} - {{ $label }}"
                    @checked((int) $selectedRating === $score) @disabled($ratingDisabled)
                    class="h-5 w-5 accent-emerald-700 focus:ring-2 focus:ring-emerald-600">
            </label>
        </td>
    @endforeach
</tr>
