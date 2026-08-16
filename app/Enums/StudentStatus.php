<?php

namespace App\Enums;

enum StudentStatus: string
{
    case REGULAR = 'regular';
    case GRADUATED = 'graduated';
    case WITHDRAWN = 'withdrawn';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'Regular',
            self::GRADUATED => 'Graduado',
            self::WITHDRAWN => 'Retirado',
            self::SUSPENDED => 'Suspendido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REGULAR => 'emerald',
            self::GRADUATED => 'sky',
            self::WITHDRAWN => 'gray',
            self::SUSPENDED => 'red',
        };
    }
}
