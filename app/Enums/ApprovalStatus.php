<?php

namespace App\Enums;

enum ApprovalStatus:int
{
    case PENDING = 0; /**< Status menunggu persetujuan */

    case APPROVED = 1; /**< Status disetujui */

    case REJECTED = 2; /**< Status ditolak */
}
