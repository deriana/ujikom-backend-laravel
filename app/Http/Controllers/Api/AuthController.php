<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            [$user, $token] = $this->authService->register($request->validated());

            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
            ], 'User registered successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            [$user, $token] = $this->authService->login($request->validated());

            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
            ], 'User logged in successfully', 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {

            $this->authService->logout(Auth::user());

            return $this->successResponse(null, 'User logged out successfully', 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
