<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('applicants', 'sex')) {
                $table->string('sex')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('applicants', 'civil_status')) {
                $table->string('civil_status')->nullable()->after('sex');
            }
            if (! Schema::hasColumn('applicants', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('civil_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            if (Schema::hasColumn('applicants', 'civil_status')) {
                $table->dropColumn('civil_status');
            }
            if (Schema::hasColumn('applicants', 'sex')) {
                $table->dropColumn('sex');
            }
        });
    }
};
