<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('PDS_table')) {
            return;
        }

        Schema::table('PDS_table', function (Blueprint $table) {
            if (! Schema::hasColumn('PDS_table', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('tin_no');
            }

            if (! Schema::hasColumn('PDS_table', 'zip_code')) {
                $table->string('zip_code')->nullable()->after('permanent_address');
            }
        });

        if (Schema::hasColumn('PDS_table', 'permanent_address_zip_code')) {
            DB::table('pds_table')
                ->select(['id', 'permanent_address_zip_code'])
                ->whereNotNull('permanent_address_zip_code')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        [$address, $zipCode] = $this->splitAddressAndZipCode($row->permanent_address_zip_code);

                        DB::table('pds_table')
                            ->where('id', $row->id)
                            ->update([
                                'permanent_address' => $address,
                                'zip_code' => $zipCode,
                            ]);
                    }
                });

            Schema::table('PDS_table', function (Blueprint $table) {
                $table->dropColumn('permanent_address_zip_code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('PDS_table')) {
            return;
        }

        Schema::table('PDS_table', function (Blueprint $table) {
            if (! Schema::hasColumn('PDS_table', 'permanent_address_zip_code')) {
                $table->text('permanent_address_zip_code')->nullable()->after('tin_no');
            }
        });

        DB::table('pds_table')
            ->select(['id', 'permanent_address', 'zip_code'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $combined = collect([$row->permanent_address, $row->zip_code])
                        ->filter(fn ($value) => filled($value))
                        ->implode(' ');

                    DB::table('pds_table')
                        ->where('id', $row->id)
                        ->update(['permanent_address_zip_code' => $combined !== '' ? $combined : null]);
                }
            });

        Schema::table('PDS_table', function (Blueprint $table) {
            if (Schema::hasColumn('PDS_table', 'zip_code')) {
                $table->dropColumn('zip_code');
            }

            if (Schema::hasColumn('PDS_table', 'permanent_address')) {
                $table->dropColumn('permanent_address');
            }
        });
    }

    private function splitAddressAndZipCode(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [null, null];
        }

        if (preg_match('/^(.*?)(?:\s+)?(?:zip\s*code\s*)?(\d{4})(?:\D*)$/i', $value, $matches)) {
            $address = trim($matches[1]);
            $zipCode = trim($matches[2]);

            return [$address !== '' ? $address : null, $zipCode];
        }

        return [$value, null];
    }
};
