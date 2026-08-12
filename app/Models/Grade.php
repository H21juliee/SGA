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
        'council_adjustment',
    ];

    protected $casts = [
        'score'              => 'decimal:2',
        'council_adjustment' => 'integer',
    ];

    /**
     * Nota definitiva del lapso = nota del docente + ajuste de consejo.
     */
    public function getDefinitiveAttribute(): float
    {
        $definitive = (float) $this->score + (int) $this->council_adjustment;
        return max(1, min(20, $definitive));
    }

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
