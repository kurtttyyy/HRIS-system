<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pds_table')) {
            return;
        }

        Schema::table('pds_table', function (Blueprint $table) {
            if (! Schema::hasColumn('pds_table', 'college_school_name')) {
                $table->string('college_school_name')->nullable()->after('vocational_trade_course');
            }

            if (! Schema::hasColumn('pds_table', 'college_year_graduated')) {
                $table->string('college_year_graduated', 50)->nullable()->after('college_school_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pds_table')) {
            return;
        }

        Schema::table('pds_table', function (Blueprint $table) {
            foreach (['college_year_graduated', 'college_school_name'] as $column) {
                if (Schema::hasColumn('pds_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
