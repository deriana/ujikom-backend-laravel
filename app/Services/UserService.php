<?php

namespace App\Services;

use App\Models\User;
use Exception;

class UserService
{
    public function index()
    {
        $user = User::all();

        return $user;
    }
}
