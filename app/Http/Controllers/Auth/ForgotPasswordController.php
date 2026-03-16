<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Class ForgotPasswordController
 *
 * Mengelola proses pengaturan ulang kata sandi (forgot password),
 * termasuk pengiriman tautan reset, validasi token, dan pembaruan kata sandi.
 */
class ForgotPasswordController extends Controller
{
    protected $passwordResetService; /**< Layanan untuk menangani logika bisnis pengaturan ulang kata sandi */

    /**
     * Membuat instance controller baru.
     *
     * @param PasswordResetService $passwordResetService
     */
    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Mengirimkan tautan pengaturan ulang kata sandi ke email pengguna.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;
        $ip = $request->ip();

        // Rate Limiting: Max 3 requests per hour per Email/IP
        $key = 'forgot-password:' . $email . ':' . $ip;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour window

        $user = $this->passwordResetService->getUserByEmail($email);

        // Account Enumeration Protection: Always return success message
        if ($user) {
            $token = $this->passwordResetService->generateToken($user);
            Mail::to($user->email)->send(new PasswordResetMail($user, $token));
        }

        return response()->json([
            'message' => 'If this email is registered, a password reset link has been sent.',
        ]);
    }

    /**
     * Memeriksa apakah token pengaturan ulang kata sandi valid atau belum kedaluwarsa.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return response()->json(['message' => 'Token is required.'], 400);
        }

        $user = $this->passwordResetService->checkToken($token);

        if ($user) {
            return response()->json([
                'email' => $user->email,
                'message' => 'Token is valid.',
            ], 200);
        }

        return response()->json(['message' => 'Invalid or expired token.'], 410);
    }

    /**
     * Memperbarui kata sandi pengguna berdasarkan token yang valid.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $success = $this->passwordResetService->resetPassword(
            $request->token,
            $request->password
        );

        if ($success) {
            return response()->json(['message' => 'Password has been reset successfully.'], 200);
        }

        return response()->json(['message' => 'Invalid or expired token.'], 410);
    }
}
