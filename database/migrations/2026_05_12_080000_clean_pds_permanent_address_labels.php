<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('PDS_table') || ! Schema::hasColumn('PDS_table', 'permanent_address')) {
            return;
        }

        DB::table('pds_table')
            ->select(['id', 'permanent_address', 'zip_code'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $address = $this->cleanAddressLabels($row->permanent_address);
                    $zipCode = $row->zip_code;

                    if ($address !== null && blank($zipCode) && preg_match('/^(.*?)(?:\s+)?(?:zip\s*code\s*)?(\d{4})(?:\D*)$/i', $address, $matches)) {
                        $address = $this->cleanAddressLabels($matches[1]);
                        $zipCode = trim($matches[2]);
                    }

                    DB::table('pds_table')
                        ->where('id', $row->id)
                        ->update([
                            'permanent_address' => $address,
                            'zip_code' => $zipCode,
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function cleanAddressLabels(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $labels = [
            'House/Block/Lot No.',
            'House Block Lot No.',
            'No. Street',
            'Street',
            'Subdivision/Village',
            'Subdivision Village',
            'Barangay',
            'City/Municipality',
            'City Municipality',
            'Province',
        ];

        foreach ($labels as $label) {
            $value = preg_replace('/(?<!\w)'.preg_quote($label, '/').'(?!\w)/iu', ' ', $value) ?? $value;
        }

        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        $value = trim($value, " \t\n\r\0\x0B:-");

        return $value === '' ? null : $value;
    }
};
