<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case DIRECTOR = 'director';
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case HR = 'hr';
    case FINANCE = 'finance';
    case EMPLOYEE = 'employee';
}
