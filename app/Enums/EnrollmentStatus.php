<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE = 'active';
    case PROMOTED = 'promoted';
    case FAILED = 'failed';
    case WITHDRAWN = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::PROMOTED => 'Promovido',
            self::FAILED => 'Reprobado',
            self::WITHDRAWN => 'Retirado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'emerald',
            self::PROMOTED => 'sky',
            self::FAILED => 'red',
            self::WITHDRAWN => 'gray',
        };
    }
}
