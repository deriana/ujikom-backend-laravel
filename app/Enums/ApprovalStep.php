<?php

namespace App\Enums;

enum ApprovalStep:int
{
    case MANAGER = 0; /**< Tahap persetujuan oleh Manager */

    case HR = 1; /**< Tahap persetujuan oleh HR */

    case DONE = 2; /**< Tahap persetujuan selesai/disetujui sepenuhnya */

    case REJECTED = 3; /**< Tahap di mana pengajuan ditolak */
}
