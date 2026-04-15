<?php

namespace App\Enums;

enum PowerUpTypeEnum: string
{
    case ANTI_LATE_LIGHT = 'anti_late_light'; // Untuk telat ringan
    case ANTI_LATE_HARD  = 'anti_late_hard';  // Untuk telat berat
    case ABSENT_PROTECT  = 'absent_protect';  // Untuk hapus potongan mangkir
    case POINT_BOOSTER   = 'point_booster';   // Untuk double point
}
