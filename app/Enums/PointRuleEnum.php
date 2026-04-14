<?php

namespace App\Enums;

enum PointRuleEnum: string {
    case PRESENT = 'Hadir Tepat Waktu';
    case LATE    = 'Terlambat';
    case ABSENT  = 'Mangkir (Alpha)';
    case OVERTIME = 'Lembur';
    case EARLY_LEAVE = 'Pulang Cepat';
}
