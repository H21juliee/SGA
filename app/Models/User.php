<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'cedula', 'phone', 'is_active', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /* ---- Relations ---- */

    public function academicLoads(): HasMany
    {
        return $this->hasMany(AcademicLoad::class, 'teacher_id');
    }

    public function securityQuestions(): HasMany
    {
        return $this->hasMany(SecurityQuestion::class);
    }

    public function coordinatedGradeLevels(): HasMany
    {
        return $this->hasMany(GradeLevelCoordinator::class);
    }

    public function isYearCoordinatorOnly(): bool
    {
        return $this->hasRole('Coordinador de Año') && !$this->hasAnyRole(['SuperAdmin', 'Administrador']);
    }

    public function coordinatedGradeLevelIds(?int $schoolYearId = null): array
    {
        if ($schoolYearId === null) {
            $schoolYearId = SchoolYear::active()->first()?->id;
        }

        if (!$schoolYearId) {
            return [];
        }

        return $this->coordinatedGradeLevels()
            ->where('school_year_id', $schoolYearId)
            ->pluck('grade_level_id')
            ->toArray();
    }
}
