<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
        'grade_level_id',
        'school_year_id',
        'name',
        'capacity',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    /* ---- Relations ---- */

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function academicLoads(): HasMany
    {
        return $this->hasMany(AcademicLoad::class);
    }

    /* ---- Accessors ---- */

    public function getFullNameAttribute(): string
    {
        return "{$this->gradeLevel->name} - {$this->name}";
    }
}
