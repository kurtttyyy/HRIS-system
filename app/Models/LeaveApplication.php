<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'office_department',
        'employee_name',
        'filing_date',
        'position',
        'salary',
        'leave_type',
        'number_of_working_days',
        'inclusive_dates',
        'as_of_label',
        'earned_date_label',
        'beginning_vacation',
        'beginning_sick',
        'beginning_total',
        'earned_vacation',
        'earned_sick',
        'earned_total',
        'applied_vacation',
        'applied_sick',
        'applied_total',
        'ending_vacation',
        'ending_sick',
        'ending_total',
        'days_with_pay',
        'days_without_pay',
        'commutation',
        'medical_receipt_path',
        'medical_receipt_name',
        'medical_receipt_mime',
        'status',
    ];

    protected $casts = [
        'filing_date' => 'date',
        'number_of_working_days' => 'decimal:1',
        'beginning_vacation' => 'decimal:1',
        'beginning_sick' => 'decimal:1',
        'beginning_total' => 'decimal:1',
        'earned_vacation' => 'decimal:1',
        'earned_sick' => 'decimal:1',
        'earned_total' => 'decimal:1',
        'applied_vacation' => 'decimal:1',
        'applied_sick' => 'decimal:1',
        'applied_total' => 'decimal:1',
        'ending_vacation' => 'decimal:1',
        'ending_sick' => 'decimal:1',
        'ending_total' => 'decimal:1',
        'days_with_pay' => 'decimal:1',
        'days_without_pay' => 'decimal:1',
    ];
}
