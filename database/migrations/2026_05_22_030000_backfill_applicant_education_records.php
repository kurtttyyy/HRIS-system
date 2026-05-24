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
            || !Schema::hasColumn('education', 'applicant_id')
            || !Schema::hasTable('applicants')
            || !Schema::hasTable('applicant_degrees')
        ) {
            return;
        }

        DB::table('applicants')
            ->orderBy('id')
            ->chunkById(100, function ($applicants) {
                foreach ($applicants as $applicant) {
                    $this->storeEducationFromApplicant($applicant);
                }
            });
    }

    public function down(): void
    {
        // Data backfill only.
    }

    private function storeEducationFromApplicant(object $applicant): void
    {
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
        $now = now();

        $payload = [
            'applicant_id' => (int) $applicant->id,
            'user_id' => $applicant->user_id ? (int) $applicant->user_id : null,
            'elementary_school_name' => $this->clean($elementary->school_name ?? null),
            'elementary_year_finished' => $this->clean($elementary->year_finished ?? null),
            'secondary_school_name' => $this->clean($secondary->school_name ?? null),
            'secondary_year_finished' => $this->clean($secondary->year_finished ?? null),
            'vocational_trade_school_name' => $this->clean($vocationalTrade->school_name ?? null),
            'vocational_trade_year_finished' => $this->clean($vocationalTrade->year_finished ?? null),
            'bachelor' => $this->clean($applicant->bachelor_degree ?? ($bachelor->degree_name ?? null)) ?? '',
            'master' => $this->clean($applicant->master_degree ?? ($master->degree_name ?? null)) ?? '',
            'doctorate' => $this->clean($applicant->doctoral_degree ?? ($doctorate->degree_name ?? null)) ?? '',
            'updated_at' => $now,
        ];

        $existing = DB::table('education')
            ->where('applicant_id', (int) $applicant->id)
            ->first();

        if ($existing) {
            DB::table('education')
                ->where('id', (int) $existing->id)
                ->update($payload);

            return;
        }

        DB::table('education')->insert([
            ...$payload,
            'created_at' => $now,
        ]);
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }
};
