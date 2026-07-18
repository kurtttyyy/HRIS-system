<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $openPositionId = DB::table('open_positions')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        if (!$openPositionId) {
            return;
        }

        DB::table('users as u')
            ->join('employees as e', 'e.user_id', '=', 'u.id')
            ->leftJoin('applicants as a', function ($join) {
                $join->on('a.user_id', '=', 'u.id')->whereNull('a.deleted_at');
            })
            ->whereNull('a.id')
            ->whereRaw("LOWER(TRIM(COALESCE(u.role, ''))) = 'employee'")
            ->select([
                'u.id', 'u.first_name', 'u.middle_name', 'u.last_name',
                'u.position', 'u.job_role', 'e.employement_date',
            ])
            ->orderBy('u.id')
            ->each(function ($user) use ($openPositionId): void {
                DB::table('applicants')->insert([
                    'user_id' => $user->id,
                    'open_position_id' => $openPositionId,
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'email' => '',
                    'field_study' => '-',
                    'work_position' => $user->position ?: ($user->job_role ?: 'Employee'),
                    'work_employer' => 'Northeastern College',
                    'work_location' => '-',
                    'work_duration' => '-',
                    'experience_years' => '0',
                    'skills_n_expertise' => '-',
                    'application_status' => 'Hired',
                    'fresh_graduate' => false,
                    'date_hired' => $user->employement_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Internal document profiles may already own uploads, so they are retained.
    }
};
