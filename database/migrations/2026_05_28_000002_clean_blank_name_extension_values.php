<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->cleanTable('applicants');
        $this->cleanTable('pds_table');
    }

    public function down(): void
    {
        //
    }

    private function cleanTable(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'name_extension')) {
            return;
        }

        DB::table($table)
            ->select(['id', 'name_extension'])
            ->whereNotNull('name_extension')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    if ($this->isBlankNameExtension((string) $row->name_extension)) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['name_extension' => null]);
                    }
                }
            });
    }

    private function isBlankNameExtension(string $value): bool
    {
        $trimmed = trim($value);
        $normalized = Str::of($trimmed)
            ->lower()
            ->replace(['.', ',', '(', ')', '-', '/', ':', '#'], ' ')
            ->squish()
            ->toString();

        if ($normalized === '') {
            return true;
        }

        return in_array($normalized, [
            'name extension',
            'name extension jr sr',
            'jr sr',
            'na',
            'n a',
            'none',
            'not applicable',
        ], true);
    }
};
