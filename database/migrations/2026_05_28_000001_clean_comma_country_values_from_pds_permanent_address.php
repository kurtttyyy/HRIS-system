<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('PDS_table') || ! Schema::hasColumn('PDS_table', 'permanent_address')) {
            return;
        }

        DB::table('pds_table')
            ->select(['id', 'permanent_address', 'zip_code'])
            ->whereNotNull('permanent_address')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    if (filled($row->zip_code ?? null)) {
                        continue;
                    }

                    if ($this->looksLikeCitizenshipCountry((string) $row->permanent_address)) {
                        DB::table('pds_table')
                            ->where('id', $row->id)
                            ->update(['permanent_address' => null]);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function looksLikeCitizenshipCountry(string $value): bool
    {
        $normalized = Str::of($value)
            ->lower()
            ->replace(['-', '/', '.', ':', '#', ','], ' ')
            ->squish()
            ->toString();

        return in_array($normalized, [
            'bahamas',
            'bahamas the',
            'dual citizenship',
            'filipino',
        ], true);
    }
};
