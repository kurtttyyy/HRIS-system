<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermanencyEvaluationTemplate extends Model
{
    protected $fillable = ['title', 'instructions', 'notice_days', 'criteria', 'created_by'];
    protected $casts = ['criteria' => 'array', 'notice_days' => 'integer'];
}
