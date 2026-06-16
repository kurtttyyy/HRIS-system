<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('education') || ! Schema::hasColumn('education', 'user_id')) {
            return;
        }

        if ($this->userIdAllowsNull()) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteEducationTable();

            return;
        }

        Schema::table('education', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        //
    }

    private function userIdAllowsNull(): bool
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return true;
        }

        foreach (DB::select('PRAGMA table_info(education)') as $column) {
            if (($column->name ?? null) === 'user_id') {
                return (int) ($column->notnull ?? 0) === 0;
            }
        }

        return true;
    }

    private function rebuildSqliteEducationTable(): void
    {
        $columns = collect(DB::select('PRAGMA table_info(education)'))
            ->pluck('name')
            ->all();

        $copyColumns = array_values(array_intersect([
            'id',
            'applicant_id',
            'user_id',
            'elementary_school_name',
            'elementary_year_finished',
            'secondary_school_name',
            'secondary_year_finished',
            'vocational_trade_school_name',
            'vocational_trade_year_finished',
            'college_school_name',
            'college_year_finished',
            'bachelor',
            'master',
            'doctorate',
            'deleted_at',
            'created_at',
            'updated_at',
        ], $columns));

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::statement(<<<'SQL'
                CREATE TABLE education_rebuild (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    applicant_id INTEGER NULL,
                    user_id INTEGER NULL,
                    elementary_school_name VARCHAR NULL,
                    elementary_year_finished VARCHAR(50) NULL,
                    secondary_school_name VARCHAR NULL,
                    secondary_year_finished VARCHAR(50) NULL,
                    vocational_trade_school_name VARCHAR NULL,
                    vocational_trade_year_finished VARCHAR(50) NULL,
                    college_school_name VARCHAR NULL,
                    college_year_finished VARCHAR(50) NULL,
                    bachelor VARCHAR NOT NULL DEFAULT '',
                    master VARCHAR NOT NULL DEFAULT '',
                    doctorate VARCHAR NOT NULL DEFAULT '',
                    deleted_at DATETIME NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY(applicant_id) REFERENCES applicants(id) ON DELETE SET NULL,
                    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            SQL);

            if ($copyColumns !== []) {
                $columnList = implode(', ', array_map(fn ($column) => '"'.$column.'"', $copyColumns));

                DB::statement("INSERT INTO education_rebuild ({$columnList}) SELECT {$columnList} FROM education");
            }

            DB::statement('DROP TABLE education');
            DB::statement('ALTER TABLE education_rebuild RENAME TO education');
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
