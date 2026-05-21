<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (self $applicant) {
            $applicant->syncLinkedUserDepartmentAndPositionFromOpenPosition();
        });

        static::restored(function (self $applicant) {
            $applicant->syncLinkedUserDepartmentAndPositionFromOpenPosition();
        });
    }

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'email',
        'phone',
        'sex',
        'civil_status',
        'date_of_birth',
        'address',
        'bachelor_degree',
        'bachelor_school_name',
        'bachelor_year_finished',
        'master_degree',
        'master_school_name',
        'master_year_finished',
        'doctoral_degree',
        'doctoral_school_name',
        'doctoral_year_finished',
        'field_study',
        'skills_n_expertise',
        'benefit',
        'open_position_id',
        'application_status',
        'user_id',
        'work_position',
        'work_employer',
        'work_location',
        'work_duration',
        'fresh_graduate',
        'starRatings',
        'experience_years',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Adjust 'user_id' if your column name differs
    }

    public function position(){
        return $this->belongsTo(OpenPosition::class, 'open_position_id', 'id');
    }

    public function documents(){
        return $this->hasMany(ApplicantDocument::class, 'applicant_id', 'id');
    }

    public function degrees()
    {
        return $this->hasMany(ApplicantDegree::class, 'applicant_id', 'id');
    }

    public function bachelorDegrees()
    {
        return $this->hasMany(ApplicantDegree::class, 'applicant_id', 'id')
            ->where('degree_level', 'bachelor')
            ->orderBy('sort_order');
    }

    protected $casts = [
        'date_hired' => 'date',
        'date_of_birth' => 'date',
        'fresh_graduate' => 'boolean',
        'starRatings' => 'integer',
    ];

    protected $appends = [
        'formatted_date_hired',
    ];

    public function getFormattedDateHiredAttribute()
    {
        return $this->date_hired
            ? $this->date_hired->format('F j, Y')
            : '';
    }

    private function syncLinkedUserDepartmentAndPositionFromOpenPosition(): void
    {
        $userId = (int) ($this->user_id ?? 0);
        $openPositionId = (int) ($this->open_position_id ?? 0);
        if ($userId <= 0 || $openPositionId <= 0) {
            return;
        }

        $openPosition = OpenPosition::query()->find($openPositionId);
        if (!$openPosition) {
            return;
        }

        $department = trim((string) ($openPosition->department ?? ''));
        $position = trim((string) ($openPosition->title ?? ''));
        if ($department === '' && $position === '') {
            return;
        }

        $user = User::query()->find($userId);
        if (!$user) {
            return;
        }

        $payload = [];
        if ($department !== '' && trim((string) ($user->department ?? '')) !== $department) {
            $payload['department'] = $department;
        }
        if ($position !== '' && trim((string) ($user->position ?? '')) !== $position) {
            $payload['position'] = $position;
        }

        if (!empty($payload)) {
            $user->update($payload);
        }
    }
}

