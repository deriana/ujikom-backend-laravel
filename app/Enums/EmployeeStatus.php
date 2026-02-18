<?php

namespace App\Enums;

enum EmployeeStatus:int
{
    case PERMANENT = 0;
    case CONTRACT = 1;
    case INTERN = 2;
    case PROBATION = 3;

    public function label(): string
    {
        return match($this) {
            self::PERMANENT => 'Permanent',
            self::CONTRACT => 'Contract',
            self::INTERN => 'Intern',
            self::PROBATION => 'Probation',
        };
    }
}
