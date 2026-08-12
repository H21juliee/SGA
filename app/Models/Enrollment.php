<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\EnrollmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'section_id',
        'school_year_id',
        'status',
        'enrollment_type',
        'enrolled_at',
    ];

    protected $casts = [
        'status' => EnrollmentStatus::class,
        'enrollment_type' => EnrollmentType::class,
        'enrolled_at' => 'date',
    ];

    /* ---- Relations ---- */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function subjectDebts(): HasMany
    {
        return $this->hasMany(SubjectDebt::class, 'origin_enrollment_id');
    }

    public function resolvedDebts(): HasMany
    {
        return $this->hasMany(SubjectDebt::class, 'resolution_enrollment_id');
    }

    public function revisionGrades(): HasMany
    {
        return $this->hasMany(RevisionGrade::class);
    }

    /* ---- Scopes ---- */

    public function scopeActive($query)
    {
        return $query->where('status', EnrollmentStatus::ACTIVE);
    }

    public function scopeForSchoolYear($query, int $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'student',
            'section.gradeLevel',
            'grades.subject',
            'grades.lapse',
        ]);
    }
}
