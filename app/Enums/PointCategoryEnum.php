<?php

namespace App\Enums;

enum PointCategoryEnum: string
{
    case ATTENDANCE = 'attendance';
    case PERFORMANCE = 'performance';
    case DISCIPLINE = 'discipline';
    case LEARNING = 'learning';
    case ACHIEVEMENT = 'achievement';
}
