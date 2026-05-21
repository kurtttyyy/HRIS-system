<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('applicants', 'name_extension')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('name_extension')->nullable()->after('last_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('applicants', 'name_extension')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->dropColumn('name_extension');
            });
        }
    }
};
