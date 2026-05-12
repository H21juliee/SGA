<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLock extends Model
{
    protected $fillable = [
        'subject_id',
        'section_id',
        'date',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
