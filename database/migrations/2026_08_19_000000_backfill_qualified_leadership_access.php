<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'department_head')) {
            return;
        }

        DB::table('users')
            ->whereRaw("LOWER(TRIM(COALESCE(department_head, ''))) IN (?, ?, ?, ?)", ['head', 'yes', 'true', '1'])
            ->update(['department_head' => 'Approved']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('department_head')
                    ->orWhereRaw("TRIM(COALESCE(department_head, '')) = ''");
            })
            ->where(function ($query) {
                $leadershipTerms = [
                    'president',
                    'vice president',
                    'vice-president',
                    'dean',
                    'director',
                    'head',
                    'chairperson',
                    'chairman',
                    'chief',
                ];

                foreach (['position', 'job_role'] as $column) {
                    if (!Schema::hasColumn('users', $column)) {
                        continue;
                    }

                    foreach ($leadershipTerms as $term) {
                        $query->orWhereRaw(
                            "LOWER(TRIM(COALESCE({$column}, ''))) LIKE ?",
                            ["%{$term}%"]
                        );
                    }
                }
            })
            ->update(['department_head' => 'Approved']);
    }

    public function down(): void
    {
        // This data correction is intentionally not reversed because existing
        // approved leadership assignments may predate this migration.
    }
};
