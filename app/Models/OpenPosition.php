<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpenPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::updated(function (self $openPosition) {
            if (!$openPosition->wasChanged(['title', 'department'])) {
                return;
            }

            $department = trim((string) ($openPosition->department ?? ''));
            $position = trim((string) ($openPosition->title ?? ''));
            if ($department === '' && $position === '') {
                return;
            }

            $linkedUserIds = Applicant::query()
                ->where('open_position_id', $openPosition->id)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values();

            if ($linkedUserIds->isEmpty()) {
                return;
            }

            User::query()
                ->whereIn('id', $linkedUserIds->all())
                ->get()
                ->each(function (User $user) use ($department, $position) {
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
                });
        });
    }

    protected $fillable = [
        'title',
        'department',
        'employment',
        'work_mode',
        'job_description',
        'responsibilities',
        'requirements',
        // 'min_salary',
        // 'max_salary',
        'experience_level',
        'location',
        'skills',
        'benifits',
        'job_type',
        'one',
        'two',
    ];

    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'open_position_id','id');
    }
}
