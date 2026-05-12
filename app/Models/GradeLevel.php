<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends Model
{
    protected $fillable = [
        'name',
        'order_num',
    ];

    protected $casts = [
        'order_num' => 'integer',
    ];

    /* ---- Relations ---- */

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /* ---- Helpers ---- */

    public function isLast(): bool
    {
        return $this->order_num === self::max('order_num');
    }

    public function next(): ?self
    {
        return self::where('order_num', $this->order_num + 1)->first();
    }
}
