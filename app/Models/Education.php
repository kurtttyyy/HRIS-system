<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Education extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'education';

    protected $fillable = [
        'applicant_id',
        'user_id',
        'elementary_school_name',
        'elementary_year_finished',
        'secondary_school_name',
        'secondary_year_finished',
        'vocational_trade_school_name',
        'vocational_trade_year_finished',
        'bachelor',
        'master',
        'doctorate',
    ];
}
