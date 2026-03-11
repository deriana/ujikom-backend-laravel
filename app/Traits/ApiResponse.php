<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 *
 * Trait pembantu untuk standarisasi format respon JSON di seluruh aplikasi.
 */
trait ApiResponse
{
    /**
     * Mengirimkan respon JSON sukses yang terstandarisasi.
     *
     * @param mixed $data Data yang akan dikembalikan dalam respon.
     * @param string $message Pesan sukses (default: 'Success').
     * @param int $code Kode status HTTP (default: 200).
     * @return JsonResponse
     */
    public function successResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Mengirimkan respon JSON kesalahan yang terstandarisasi.
     *
     * @param string $message Pesan kesalahan.
     * @param int $code Kode status HTTP.
     * @param mixed|null $errors Detail kesalahan tambahan atau validasi.
     * @return JsonResponse
     */
    public function errorResponse(string $message, int $code, $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
