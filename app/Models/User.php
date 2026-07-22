<?php

namespace App\Models;

use App\Notifications\EmployeePasswordResetNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Applicant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::created(function (self $user) {
            $user->syncEmployeeRecord();
            $user->syncApplicantRecord();
        });

        static::updated(function (self $user) {
            if ($user->wasChanged(['role', 'department', 'position', 'job_role', 'email', 'first_name', 'last_name', 'middle_name'])) {
                $user->syncEmployeeRecord();
                $user->syncApplicantRecord();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'role',
        'job_role',
        'position',
        'department',
        'department_head',
        'status',
        'account_status',
        'email',
        'password',
        'temporary_pin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'temporary_pin' => 'encrypted',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new EmployeePasswordResetNotification($token));
    }

    public function applicant(){
        return $this->hasOne(Applicant::class, 'user_id', 'id')
                    ->whereRaw('LOWER(application_status) = ?', ['hired'])
                    ->latestOfMany();
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', '!=', 'Employee');
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('F j, Y') : null;
    }



    public function getInitialsAttribute()
    {
        return strtoupper(
            substr($this->first_name, 0, 1) .
            substr($this->last_name, 0, 1)
        );
    }

    public function employee(){
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    public function education(){
        return $this->hasOne(Education::class, 'user_id', 'id');
    }

    public function government(){
        return $this->hasOne(Government::class, 'user_id', 'id');
    }

    public function license(){
        return $this->hasOne(License::class, 'user_id', 'id');
    }

    public function salary(){
        return $this->hasOne(Salary::class, 'user_id', 'id');
    }

    public function resignations()
    {
        return $this->hasMany(Resignation::class, 'user_id', 'id');
    }

    public function positionHistories()
    {
        return $this->hasMany(EmployeePositionHistory::class, 'user_id', 'id');
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'user_id', 'id');
    }

    private function syncEmployeeRecord(): void
    {
        if (strcasecmp(trim((string) ($this->role ?? '')), 'Employee') !== 0) {
            return;
        }

        if (!Schema::hasTable('employees')) {
            return;
        }

        $employee = Employee::firstOrNew(['user_id' => $this->id]);

        if (!$employee->exists) {
            $employee->employee_id = '';
            $employee->employement_date = optional($this->created_at)->toDateString() ?? now()->toDateString();
            $employee->birthday = now()->subYears(18)->toDateString();
            $employee->account_number = 'N/A';
            $employee->sex = 'Unspecified';
            $employee->civil_status = 'Single';
            $employee->contact_number = 'N/A';
            $employee->address = 'N/A';
            $employee->classification = 'Probationary';
        }

        $openPosition = DB::table('applicants as a')
            ->join('open_positions as op', 'op.id', '=', 'a.open_position_id')
            ->where('a.user_id', $this->id)
            ->whereNull('a.deleted_at')
            ->whereNull('op.deleted_at')
            ->orderByDesc('a.id')
            ->select([
                DB::raw("NULLIF(TRIM(op.department), '') as op_department"),
                DB::raw("NULLIF(TRIM(op.title), '') as op_title"),
            ])
            ->first();

        $openDepartment = trim((string) ($openPosition->op_department ?? ''));
        $openTitle = trim((string) ($openPosition->op_title ?? ''));
        $userDepartment = trim((string) ($this->department ?? ''));
        $userPosition = trim((string) ($this->position ?? ''));
        $userJobRole = trim((string) ($this->job_role ?? ''));

        $employee->department = $openDepartment !== ''
            ? $openDepartment
            : ($userDepartment !== '' ? $userDepartment : 'Unassigned');

        $employee->position = $openTitle !== ''
            ? $openTitle
            : ($userPosition !== ''
                ? $userPosition
                : ($userJobRole !== '' ? $userJobRole : 'Employee'));

        $employee->email = trim((string) ($this->email ?? '')) !== '' ? $this->email : ($employee->email ?: null);

        $employee->save();
    }

    private function syncApplicantRecord(): void
    {
        if (strcasecmp(trim((string) ($this->role ?? '')), 'Employee') !== 0) {
            return;
        }

        $email = trim((string) ($this->email ?? ''));
        $firstName = trim((string) ($this->first_name ?? ''));
        $lastName = trim((string) ($this->last_name ?? ''));

        $applicant = Applicant::query()
            ->where('user_id', $this->id)
            ->orderByDesc('id')
            ->first();

        if (!$applicant && $email !== '') {
            $applicant = Applicant::query()
                ->whereNull('user_id')
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($email)])
                ->orderByDesc('id')
                ->first();
        }

        if (!$applicant) {
            $openPositionId = $this->resolveSyncOpenPositionId();

            if (!$openPositionId) {
                return;
            }

            $safeEmail = $email !== '' ? $email : ('employee-'.$this->id.'@placeholder.local');

            $applicant = Applicant::create([
                'user_id' => $this->id,
                'open_position_id' => (int) $openPositionId,
                'first_name' => $firstName !== '' ? $firstName : 'Employee',
                'last_name' => $lastName !== '' ? $lastName : ('#'.$this->id),
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

        $payload = ['user_id' => $this->id];

        if ($email !== '') {
            $payload['email'] = $email;
        }

        if ($firstName !== '') {
            $payload['first_name'] = $firstName;
        }

        if ($lastName !== '') {
            $payload['last_name'] = $lastName;
        }

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
