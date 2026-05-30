<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('education')) {
            return;
        }

        Schema::table('education', function (Blueprint $table) {
            if (! Schema::hasColumn('education', 'college_school_name')) {
                $table->string('college_school_name')->nullable()->after('vocational_trade_year_finished');
            }

            if (! Schema::hasColumn('education', 'college_year_finished')) {
                $table->string('college_year_finished', 50)->nullable()->after('college_school_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('education')) {
            return;
        }

        Schema::table('education', function (Blueprint $table) {
            foreach (['college_year_finished', 'college_school_name'] as $column) {
                if (Schema::hasColumn('education', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
