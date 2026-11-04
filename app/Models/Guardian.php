<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'cedula',
        'first_name',
        'last_name',
        'nationality',
        'birth_place',
        'birth_date',
        'education_level',
        'address',
        'is_working',
        'work_sector',
        'work_type',
        'occupation',
        'phone',
        'phone_home',
        'email',
        'relationship',
    ];

    protected $casts = [
        'is_working' => 'boolean',
        'birth_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'name',
        'full_name',
    ];

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}