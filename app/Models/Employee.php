<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (self $employee) {
            $employee->syncApplicantRecord();
        });
    }

    protected $fillable = [
        'user_id',
        'employee_id',
        'email',
        'employement_date',
        'birthday',
        'account_number',
        'sex',
        'civil_status',
        'contact_number',
        'address',
        'department',
        'position',
        'classification',
        'classification_salary',
        'job_type',
        'service_record_rows',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
    ];

    protected $casts = [
        'employement_date' => 'date',
        'birthday' => 'date',
        'service_record_rows' => 'array',
    ];

    protected $appends = [
        'formatted_employement_date',
        'formatted_birthday',
    ];

    public function getFormattedEmployementDateAttribute()
    {
        return $this->employement_date ? $this->employement_date->format('F j, Y') : null;
    }

    public function getFormattedBirthdayAttribute()
    {
        return $this->birthday ? $this->birthday->format('F j, Y') : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    private function syncApplicantRecord(): void
    {
        $userId = (int) ($this->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $user = User::query()->find($userId);
        if (!$user || strcasecmp(trim((string) ($user->role ?? '')), 'Employee') !== 0) {
            return;
        }

        $normalize = static function ($value): ?string {
            $text = trim((string) ($value ?? ''));
            if ($text === '') {
                return null;
            }
            $lower = strtolower($text);
            if (in_array($lower, ['n/a', '-', 'na'], true)) {
                return null;
            }
            return $text;
        };

        $applicant = Applicant::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $emailForMatch = $normalize($this->email) ?? $normalize($user->email);
        if (!$applicant && $emailForMatch) {
            $applicant = Applicant::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($emailForMatch)])
                ->orderByDesc('id')
                ->first();
        }

        if (!$applicant) {
            $openPositionId = $this->resolveSyncOpenPositionId();

            if (!$openPositionId) {
                return;
            }

            $safeEmail = $emailForMatch ?: ('employee-'.$userId.'@placeholder.local');
            $applicant = Applicant::create([
                'user_id' => $userId,
                'open_position_id' => (int) $openPositionId,
                'first_name' => $normalize($user->first_name) ?: 'Employee',
                'last_name' => $normalize($user->last_name) ?: ('#'.$userId),
                'email' => $safeEmail,
                'field_study' => '-',
                'work_position' => '-',
                'work_employer' => '-',
                'work_location' => '-',
                'work_duration' => '-',
                'experience_years' => '0',
                'skills_n_expertise' => '-',
                'application_status' => 'Hired',
                'fresh_graduate' => false,
            ]);
        }

        $payload = [
            'user_id' => $userId,
            'first_name' => $normalize($user->first_name) ?: $applicant->first_name,
            'last_name' => $normalize($user->last_name) ?: $applicant->last_name,
            'email' => $emailForMatch ?: $applicant->email,
            'phone' => $normalize($this->contact_number) ?: $applicant->phone,
            'address' => $normalize($this->address) ?: $applicant->address,
            'work_position' => $normalize($this->position) ?: $applicant->work_position,
            'date_hired' => $this->employement_date ?: $applicant->date_hired,
        ];

        $applicant->update($payload);
    }

    private function resolveSyncOpenPositionId(): ?int
    {
        $openPositionId = DB::table('open_positions')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        if ($openPositionId) {
            return (int) $openPositionId;
        }

        $fallback = OpenPosition::query()->create([
            'title' => 'Unassigned Employee',
            'department' => 'General',
            'employment' => 'Full-Time',
            'work_mode' => 'Onsite',
            'job_description' => 'Auto-generated fallback position for employee sync.',
            'responsibilities' => '-',
            'requirements' => '-',
            'experience_level' => 'Entry Level',
            'location' => 'N/A',
            'skills' => '-',
            'benifits' => '-',
            'job_type' => 'NT',
        ]);

        return (int) $fallback->id;
    }
}
