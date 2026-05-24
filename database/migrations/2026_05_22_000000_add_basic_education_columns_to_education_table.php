<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education', function (Blueprint $table) {
            if (!Schema::hasColumn('education', 'elementary_school_name')) {
                $table->string('elementary_school_name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('education', 'elementary_year_finished')) {
                $table->string('elementary_year_finished', 50)->nullable()->after('elementary_school_name');
            }
            if (!Schema::hasColumn('education', 'secondary_school_name')) {
                $table->string('secondary_school_name')->nullable()->after('elementary_year_finished');
            }
            if (!Schema::hasColumn('education', 'secondary_year_finished')) {
                $table->string('secondary_year_finished', 50)->nullable()->after('secondary_school_name');
            }
            if (!Schema::hasColumn('education', 'vocational_trade_school_name')) {
                $table->string('vocational_trade_school_name')->nullable()->after('secondary_year_finished');
            }
            if (!Schema::hasColumn('education', 'vocational_trade_year_finished')) {
                $table->string('vocational_trade_year_finished', 50)->nullable()->after('vocational_trade_school_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('education', function (Blueprint $table) {
            foreach ([
                'vocational_trade_year_finished',
                'vocational_trade_school_name',
                'secondary_year_finished',
                'secondary_school_name',
                'elementary_year_finished',
                'elementary_school_name',
            ] as $column) {
                if (Schema::hasColumn('education', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
