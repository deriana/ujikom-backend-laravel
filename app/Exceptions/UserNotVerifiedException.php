<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UserNotVerifiedException extends Exception
{
    protected $email;

    public function __construct(string $email)
    {
        parent::__construct("Your account is not activated yet.");
        $this->email = $email;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'needs_activation' => true,
            'email' => $this->email,
        ], 403);
    }
}