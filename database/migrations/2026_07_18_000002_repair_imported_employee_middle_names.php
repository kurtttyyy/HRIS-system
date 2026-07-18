<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'Employee')
            ->whereNull('email')
            ->whereNotNull('middle_name')
            ->whereRaw("TRIM(middle_name) <> ''")
            ->orderBy('id')
            ->each(function ($user): void {
                $parts = preg_split(
                    '/\s+/',
                    trim((string) $user->first_name.' '.(string) $user->middle_name)
                ) ?: [];

                if (count($parts) < 2) {
                    return;
                }

                $middleStart = $this->middleNameStartIndex($parts);
                $firstName = trim(implode(' ', array_slice($parts, 0, $middleStart)));
                $middleName = trim(implode(' ', array_slice($parts, $middleStart)));

                if ($firstName === '' || $middleName === '') {
                    return;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                ]);
            });
    }

    public function down(): void
    {
        // The original ambiguous full-name split cannot be reconstructed safely.
    }

    private function middleNameStartIndex(array $parts): int
    {
        $count = count($parts);
        $normalized = array_map(
            static fn ($part) => strtolower((string) preg_replace('/[^a-z]/i', '', (string) $part)),
            $parts
        );

        $compoundPrefixes = [
            ['de', 'la'], ['de', 'los'], ['de', 'las'],
            ['dela'], ['delos'], ['delas'], ['de'], ['del'],
            ['van'], ['von'], ['san'], ['santa'],
        ];

        foreach ($compoundPrefixes as $prefix) {
            $start = $count - count($prefix) - 1;
            if ($start >= 1 && array_slice($normalized, $start, count($prefix)) === $prefix) {
                return $start;
            }
        }

        return $count - 1;
    }
};
