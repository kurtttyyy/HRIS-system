<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('education')) {
            return;
        }

        if (!Schema::hasColumn('education', 'applicant_id')) {
            Schema::table('education', function (Blueprint $table) {
                $table->foreignId('applicant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('applicants')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('education', 'user_id')) {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE education MODIFY user_id BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('education')) {
            return;
        }

        if (Schema::hasColumn('education', 'applicant_id')) {
            Schema::table('education', function (Blueprint $table) {
                $table->dropConstrainedForeignId('applicant_id');
            });
        }
    }
};
