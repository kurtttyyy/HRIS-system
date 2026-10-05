<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermanencyEvaluation extends Model
{
    protected $fillable = ['employee_user_id', 'head_user_id', 'template_id', 'due_date', 'ratings', 'overall_rating', 'recommendation', 'comments'];
    protected $casts = ['due_date' => 'date', 'ratings' => 'array'];

    public function employeeUser() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function headUser() { return $this->belongsTo(User::class, 'head_user_id'); }
    public function template() { return $this->belongsTo(PermanencyEvaluationTemplate::class, 'template_id'); }
}
