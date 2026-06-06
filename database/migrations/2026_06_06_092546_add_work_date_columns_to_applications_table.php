<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'work_date_from')) {
                $table->date('work_date_from')->nullable()->after('work_location');
            }

            if (!Schema::hasColumn('applicants', 'work_date_to')) {
                $table->date('work_date_to')->nullable()->after('work_date_from');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'work_date_to')) {
                $table->dropColumn('work_date_to');
            }

            if (Schema::hasColumn('applicants', 'work_date_from')) {
                $table->dropColumn('work_date_from');
            }
        });
    }
};
