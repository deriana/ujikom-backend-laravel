<?php

namespace App\Enums;

enum ApprovalStep:int
{
    case MANAGER = 0;
    case HR = 1;
    case DONE = 2;
    case REJECTED = 3;
}
