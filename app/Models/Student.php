<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;
    protected $fillable = [
        'first_name',
        'last_name',
        'cedula',
        'nationality',
        'birth_date',
        'birth_place',
        'gender',
        'address',
        'community_id',
        'email',
        'phone_home',
        'phone_mobile',
        'guardian_id',
        'photo_url',
        'status',
        'entry_type',
        'target_grade_level_id',
        'origin_school',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'status' => StudentStatus::class,
        'target_grade_level_id' => 'integer',
    ];

    /* ---- Accessors ---- */

    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    /* ---- Relations ---- */

    public function guardian(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function community(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subjectDebts(): HasMany
    {
        return $this->hasMany(SubjectDebt::class);
    }

    public function targetGradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'target_grade_level_id');
    }

    /**
     * Determina el ID del Año (GradeLevel) en el que le corresponde matricularse al estudiante.
     * - Si tiene matrículas previas en la institución:
     *   • Promovido / Promovido con pendientes → Año inmediato superior.
     *   • Reprobado / Retirado / Activo previo → Mismo año que cursaba.
     * - Si NO tiene matrículas previas (Nuevo ingreso o traslado):
     *   • Usa target_grade_level_id (o 1er Año por defecto si es nuevo ingreso).
     */
    public function resolveEligibleGradeLevelId(?int $firstGradeLevelId = null): ?int
    {
        $enrollments = $this->relationLoaded('enrollments')
            ? $this->enrollments
            : $this->enrollments()->with('section.gradeLevel')->get();

        if ($enrollments->isNotEmpty()) {
            $latest = $enrollments
                ->sortByDesc(fn (Enrollment $e) => sprintf('%010d-%010d', (int) $e->school_year_id, (int) $e->id))
                ->first();

            $currentLevel = $latest?->section?->gradeLevel;
            if ($currentLevel) {
                $statusVal = $latest->status instanceof EnrollmentStatus ? $latest->status->value : (string) $latest->status;
                if (in_array($statusVal, [EnrollmentStatus::PROMOTED->value, EnrollmentStatus::PROMOTED_PENDING->value], true)) {
                    $nextLevel = $currentLevel->next();
                    return $nextLevel ? (int) $nextLevel->id : (int) $currentLevel->id;
                }

                return (int) $currentLevel->id;
            }
        }

        if ($this->target_grade_level_id) {
            return (int) $this->target_grade_level_id;
        }

        return $firstGradeLevelId ?? GradeLevel::orderBy('order_num')->value('id');
    }

    /* ---- Scopes ---- */

    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::REGULAR);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('cedula', 'like', "%{$term}%");
        });
    }
}
