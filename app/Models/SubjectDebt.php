<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectDebt extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'origin_school_year_id',
        'origin_enrollment_id',
        'resolution_enrollment_id',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /* ---- Relations ---- */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function originSchoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'origin_school_year_id');
    }

    public function originEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'origin_enrollment_id');
    }

    public function resolutionEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'resolution_enrollment_id');
    }

    /* ---- Scopes ---- */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
