<?php

namespace App\Enums;

enum EnrollmentType: string
{
    case REGULAR = 'regular';
    case PENDING = 'pending';
    case REPEATER = 'repeater';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'Regular',
            self::PENDING => 'Con Pendientes',
            self::REPEATER => 'Repitiente',
        };
    }
}
