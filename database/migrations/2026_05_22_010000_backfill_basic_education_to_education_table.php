<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('education')
            || !Schema::hasTable('applicants')
            || !Schema::hasTable('applicant_degrees')
        ) {
            return;
        }

        $requiredColumns = [
            'elementary_school_name',
            'elementary_year_finished',
            'secondary_school_name',
            'secondary_year_finished',
            'vocational_trade_school_name',
            'vocational_trade_year_finished',
        ];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('education', $column)) {
                return;
            }
        }

        DB::table('applicants')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($applicants) {
                foreach ($applicants as $applicant) {
                    $this->backfillApplicantEducation($applicant);
                }
            });
    }

    public function down(): void
    {
        // Data backfill only. The schema migration owns these columns.
    }

    private function backfillApplicantEducation(object $applicant): void
    {
        $userId = (int) ($applicant->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $degrees = DB::table('applicant_degrees')
            ->where('applicant_id', (int) $applicant->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($degree) => strtolower(trim((string) ($degree->degree_level ?? ''))));

        $firstDegree = fn (string $level) => optional($degrees->get($level))->first();
        $elementary = $firstDegree('elementary');
        $secondary = $firstDegree('secondary');
        $vocationalTrade = $firstDegree('vocational_trade');
        $bachelor = $firstDegree('bachelor');
        $master = $firstDegree('master');
        $doctorate = $firstDegree('doctorate');

        $existing = DB::table('education')->where('user_id', $userId)->first();
        $now = now();

        $payload = [
            'elementary_school_name' => $this->pickValue($elementary->school_name ?? null, $existing->elementary_school_name ?? null),
            'elementary_year_finished' => $this->pickValue($elementary->year_finished ?? null, $existing->elementary_year_finished ?? null),
            'secondary_school_name' => $this->pickValue($secondary->school_name ?? null, $existing->secondary_school_name ?? null),
            'secondary_year_finished' => $this->pickValue($secondary->year_finished ?? null, $existing->secondary_year_finished ?? null),
            'vocational_trade_school_name' => $this->pickValue($vocationalTrade->school_name ?? null, $existing->vocational_trade_school_name ?? null),
            'vocational_trade_year_finished' => $this->pickValue($vocationalTrade->year_finished ?? null, $existing->vocational_trade_year_finished ?? null),
            'bachelor' => $this->pickValue($applicant->bachelor_degree ?? ($bachelor->degree_name ?? null), $existing->bachelor ?? null) ?? '',
            'master' => $this->pickValue($applicant->master_degree ?? ($master->degree_name ?? null), $existing->master ?? null) ?? '',
            'doctorate' => $this->pickValue($applicant->doctoral_degree ?? ($doctorate->degree_name ?? null), $existing->doctorate ?? null) ?? '',
            'updated_at' => $now,
        ];

        if ($existing) {
            DB::table('education')
                ->where('id', (int) $existing->id)
                ->update($payload);

            return;
        }

        DB::table('education')->insert([
            ...$payload,
            'user_id' => $userId,
            'created_at' => $now,
        ]);
    }

    private function pickValue(mixed $newValue, mixed $existingValue): ?string
    {
        $newValue = trim((string) ($newValue ?? ''));
        if ($newValue !== '') {
            return $newValue;
        }

        $existingValue = trim((string) ($existingValue ?? ''));
        return $existingValue !== '' ? $existingValue : null;
    }
};
