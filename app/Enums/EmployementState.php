<?php

namespace App\Enums;

enum EmploymentState:string
{
    case ACTIVE = 'active';
    case RESIGNED = 'resigned';
    case TERMINATED = 'terminated';
}
