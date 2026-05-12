<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'grade_level_id',
        'weight',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    /* ---- Relations ---- */

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function academicLoads(): HasMany
    {
        return $this->hasMany(AcademicLoad::class);
    }
}
