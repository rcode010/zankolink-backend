<?php

namespace App\Enums;

enum MoodleAllowedRoles: string
{
    case student = 'student';
    case lecturer = 'lecturer';
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
