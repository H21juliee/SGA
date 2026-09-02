<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;
    protected $fillable = [
        'first_name',
        'last_name',
        'cedula',
        'birth_date',
        'gender',
        'address',
        'guardian_id',
        'photo_url',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'status' => StudentStatus::class,
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

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subjectDebts(): HasMany
    {
        return $this->hasMany(SubjectDebt::class);
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
