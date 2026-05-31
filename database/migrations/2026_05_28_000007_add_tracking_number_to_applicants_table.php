<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->unique()->after('open_position_id');
            }
        });

        DB::table('applicants')
            ->whereNull('tracking_number')
            ->orderBy('id')
            ->select(['id', 'created_at'])
            ->chunkById(100, function ($applicants) {
                foreach ($applicants as $applicant) {
                    do {
                        $date = $applicant->created_at
                            ? \Illuminate\Support\Carbon::parse($applicant->created_at)->format('Ymd')
                            : now()->format('Ymd');
                        $trackingNumber = 'APP-'.$date.'-'.Str::upper(Str::random(6));
                    } while (DB::table('applicants')->where('tracking_number', $trackingNumber)->exists());

                    DB::table('applicants')
                        ->where('id', $applicant->id)
                        ->update(['tracking_number' => $trackingNumber]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'tracking_number')) {
                $table->dropUnique(['tracking_number']);
                $table->dropColumn('tracking_number');
            }
        });
    }
};
