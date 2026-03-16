<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Class UserNotVerifiedException
 *
 * Exception yang dilemparkan ketika pengguna mencoba login namun akunnya belum diverifikasi atau diaktivasi.
 */
class UserNotVerifiedException extends Exception
{
    protected $email; /**< Alamat email pengguna yang belum terverifikasi */

    /**
     * Membuat instance exception baru.
     *
     * @param string $email Alamat email pengguna
     */
    public function __construct(string $email)
    {
        parent::__construct("Your account is not activated yet.");
        $this->email = $email;
    }

    /**
     * Mengubah exception menjadi respon HTTP JSON.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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
