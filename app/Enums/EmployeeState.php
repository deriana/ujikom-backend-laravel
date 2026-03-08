<?php

namespace App\Enums;

enum EmployeeState: string
{
    case ACTIVE = 'active';
    case RESIGNED = 'resigned';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Aktif',
            self::RESIGNED => 'Mengundurkan Diri',
            self::TERMINATED => 'Diberhentikan',
        };
    }
}