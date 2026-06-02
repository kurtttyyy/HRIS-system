<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resignation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'employee_name',
        'department',
        'position',
        'submitted_at',
        'effective_date',
        'reason',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'status',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'submitted_at' => 'date',
        'effective_date' => 'date',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by', 'id');
    }
}
