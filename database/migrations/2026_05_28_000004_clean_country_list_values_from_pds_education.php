<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('PDS_table')) {
            return;
        }

        $columns = [
            'elementary',
            'secondary',
            'vocational_trade_course',
            'college_school_name',
            'college_degree',
            'elementary_school_name',
            'secondary_school_name',
            'vocational_trade_school_name',
        ];

        $existingColumns = collect($columns)
            ->filter(fn ($column) => Schema::hasColumn('PDS_table', $column))
            ->values()
            ->all();

        if ($existingColumns === []) {
            return;
        }

        DB::table('pds_table')
            ->select(array_merge(['id'], $existingColumns))
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($existingColumns) {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($existingColumns as $column) {
                        $value = $row->{$column} ?? null;
                        if ($value !== null && $this->isCountryListValue((string) $value)) {
                            $updates[$column] = null;
                        }
                    }

                    if ($updates !== []) {
                        DB::table('pds_table')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function isCountryListValue(string $value): bool
    {
        $normalized = Str::of($value)
            ->lower()
            ->replace([',', "'", '-', '/', '.', ':', '#'], ' ')
            ->squish()
            ->toString();

        return in_array($normalized, [
            'congo republic of the',
            'costa rica',
            'cote d ivoire',
            'croatia',
            'cuba',
            'curacao',
        ], true);
    }
};
