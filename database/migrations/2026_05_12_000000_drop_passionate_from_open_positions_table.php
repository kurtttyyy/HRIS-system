<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('open_positions', 'passionate')) {
            Schema::table('open_positions', function (Blueprint $table) {
                $table->dropColumn('passionate');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('open_positions', 'passionate')) {
            Schema::table('open_positions', function (Blueprint $table) {
                $table->text('passionate')->nullable()->after('job_type');
            });
        }
    }
};
