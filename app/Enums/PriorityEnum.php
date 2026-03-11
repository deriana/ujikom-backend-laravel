<?php

namespace  App\Enums;

/**
 * Enum PriorityEnum
 *
 * Mendefinisikan tingkat prioritas untuk tugas atau pengajuan dalam sistem.
 */
enum PriorityEnum: int
{
    case LEVEL_1 = 1; /**< Prioritas tingkat 1 (Rendah/Normal) */
    case LEVEL_2 = 2; /**< Prioritas tingkat 2 (Tinggi/Mendesak) */
}
