<?php

namespace App\Enums;

enum CrudAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case RESTORED = 'restored';
    case FORCE_DELETED = 'force_deleted';

    // Users Specific Actions
    case TERMINATED = 'terminated';
    case RESIGNED = 'resigned';
    case PHK = 'phk';
}
