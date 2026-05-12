<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = [
        'enrollment_id',
        'subject_id',
        'lapse_id',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    /* ---- Relations ---- */

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function lapse(): BelongsTo
    {
        return $this->belongsTo(Lapse::class);
    }
}
