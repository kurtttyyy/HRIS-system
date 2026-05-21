<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('open_positions', 'collage_name')) {
            Schema::table('open_positions', function (Blueprint $table) {
                $table->dropColumn('collage_name');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('open_positions', 'collage_name')) {
            Schema::table('open_positions', function (Blueprint $table) {
                $table->string('collage_name')->nullable()->after('department');
            });
        }
    }
};
