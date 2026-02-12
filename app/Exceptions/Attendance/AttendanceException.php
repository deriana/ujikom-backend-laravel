<?php

namespace App\Exceptions\Attendance;

use Exception;

class AttendanceException extends Exception
{
    protected $context = [];

    public function __construct(string $message, array $context = [], int $code = 400)
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
