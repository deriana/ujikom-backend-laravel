<?php

namespace App\Enums;

/**
 * Enum StatusEnum
 *
 * Mendefinisikan berbagai status umum untuk entitas atau proses dalam sistem.
 */
enum StatusEnum: int
{
    case pending = 1; /**< Status menunggu proses atau persetujuan */
    case approved = 2; /**< Status telah disetujui */
    case rejected = 3; /**< Status ditolak */
    case draft = 4; /**< Status draf atau konsep */
    case cancelled = 5; /**< Status dibatalkan */
    case expired = 6; /**< Status kedaluwarsa */
    case archived = 7; /**< Status diarsipkan */
    case deleted = 8; /**< Status dihapus */
    case completed = 9; /**< Status selesai atau tuntas */
}
