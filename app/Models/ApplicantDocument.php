<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'applicant_id',
        'filename',
        'filepath',
        'size',
        'mime_type',
        'type',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'created_at' => 'date',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_created_at',
        'formatted_size',
    ];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('F j, Y')
            : '';
    }

    public function getFormattedSizeAttribute()
    {
        if (!is_numeric($this->size) || $this->size < 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size;
        $power = $size > 0 ? (int) floor(log($size, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $size / (1024 ** $power);
        return number_format($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }

    public function applicants(){
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }
}
