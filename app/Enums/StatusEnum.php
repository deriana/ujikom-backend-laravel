<?php

namespace App\Enums;

enum StatusEnum: int
{
    case pending = 1;
    case approved = 2;
    case rejected = 3;
    case draft = 4;
    case cancelled = 5;
    case expired = 6;
    case archived = 7;
    case deleted = 8;
}
