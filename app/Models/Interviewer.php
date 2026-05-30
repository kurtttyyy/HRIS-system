<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interviewer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'applicant_id',
        'interview_type',
        'date',
        'time',
        'duration',
        'ended_at',
        'interviewers',
        'email_link',
        'url',
        'notes',
    ];

    public function applicant(){
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    protected $casts = [
        'date' => 'date',
        'ended_at' => 'datetime',
    ];
}
