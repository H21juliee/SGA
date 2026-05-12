<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Presente',
            self::ABSENT => 'Ausente',
            self::LATE => 'Tardanza',
            self::EXCUSED => 'Justificado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PRESENT => 'emerald',
            self::ABSENT => 'red',
            self::LATE => 'amber',
            self::EXCUSED => 'sky',
        };
    }
}
