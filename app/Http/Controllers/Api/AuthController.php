<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class AuthController
 *
 * Controller untuk menangani proses autentikasi pengguna, termasuk registrasi,
 * login, dan logout menggunakan token (Sanctum).
 */
class AuthController extends Controller
{
    protected AuthService $authService; /**< Instance dari AuthService untuk logika bisnis autentikasi */

    /**
     * Membuat instance AuthController baru.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Mendaftarkan pengguna baru ke dalam sistem.
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->register($request->validated());

        return $this->successResponse([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    /**
     * Melakukan proses autentikasi pengguna dan memberikan token akses.
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token] = $this->authService->login($request->validated());

        Log::info('user data', ['user' => $user->toArray()]);
        return $this->successResponse([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 'User logged in successfully', 200);
    }

    /**
     * Mencabut token akses pengguna yang sedang login (Logout).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout(Auth::user());

        return $this->successResponse(null, 'User logged out successfully', 200);
    }
}
