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
            'college_year_graduated',
            'elementary_school_name',
            'elementary_year_graduated',
            'secondary_school_name',
            'secondary_year_graduated',
            'vocational_trade_school_name',
            'vocational_trade_year_graduated',
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
                        if ($value !== null && $this->isBlankEducationValue((string) $value)) {
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

    private function isBlankEducationValue(string $value): bool
    {
        $normalized = Str::of($value)
            ->lower()
            ->replace(['(', ')', '-', '/', '.', ':', '#', ','], ' ')
            ->squish()
            ->toString();

        return $normalized === '' || in_array($normalized, [
            'elementary',
            'secondary',
            'vocational',
            'trade course',
            'vocational trade course',
            'college',
            'graduate studies',
            'name of school',
            'write in full',
            'basic education degree course',
            'period of attendance',
            'from',
            'to',
            'highest level units earned',
            'if not graduated',
            'year graduated',
            'scholarship academic honors received',
            'continue on separate sheet if necessary',
            'na',
            'n a',
            'none',
            'not applicable',
        ], true);
    }
};
