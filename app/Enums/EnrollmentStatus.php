<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE = 'active';
    case PROMOTED = 'promoted';
    case PROMOTED_PENDING = 'promoted_pending';
    case FAILED = 'failed';
    case WITHDRAWN = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::PROMOTED => 'Promovido',
            self::PROMOTED_PENDING => 'Promovido con Pendientes',
            self::FAILED => 'Reprobado',
            self::WITHDRAWN => 'Retirado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'emerald',
            self::PROMOTED => 'sky',
            self::PROMOTED_PENDING => 'amber',
            self::FAILED => 'red',
            self::WITHDRAWN => 'gray',
        };
    }
}
