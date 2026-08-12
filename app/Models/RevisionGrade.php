<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionGrade extends Model
{
    protected $fillable = [
        'enrollment_id',
        'subject_id',
        'score',
        'status',
        'evaluated_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'evaluated_at' => 'date',
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

    /* ---- Helpers ---- */

    public function isApproved(): bool
    {
        return $this->score >= 10;
    }
}
