<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Class VerificationController
 *
 * Mengelola proses verifikasi email pengguna, termasuk pengecekan token,
 * aktivasi akun, dan pengiriman ulang tautan verifikasi.
 */
class VerificationController extends Controller
{
    protected $verificationService; /**< Layanan untuk menangani logika bisnis verifikasi email */

    /**
     * Membuat instance controller baru.
     *
     * @param EmailVerificationService $verificationService
     */
    public function __construct(EmailVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Menangani klik tautan verifikasi dari email.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function verify(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return view('auth.verify-status', ['status' => 'invalid']);
        }

        $success = $this->verificationService->verifyToken($token);

        if ($success) {
            return view('auth.verify-status', ['status' => 'success']);
        }

        return view('auth.verify-status', ['status' => 'expired']);
    }

    /**
     * Memeriksa apakah token verifikasi valid atau belum kedaluwarsa.
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

        $user = $this->verificationService->checkToken($token);

        if ($user) {
            return response()->json([
                'email' => $user->email,
                'message' => 'Token is valid.',
            ], 200);
        }

        return response()->json(['message' => 'Invalid or expired token.'], 410);
    }

    /**
     * Menyelesaikan proses aktivasi akun dengan mengatur kata sandi pengguna.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalizeActivation(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $success = $this->verificationService->verifyAndSetPassword(
            $request->token,
            $request->password
        );

        if ($success) {
            return response()->json(['message' => 'Account activated successfully.'], 200);
        }

        return response()->json(['message' => 'Invalid or expired token.'], 410);
    }

    /**
     * Mengirimkan ulang email verifikasi ke pengguna.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;
        $ip = $request->ip();

        // Rate Limiting: Max 3 requests per hour per Email/IP
        $key = 'resend-verification:'.$email.':'.$ip;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many attempts. Please try again in '.ceil($seconds / 60).' minutes.',
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour window

        $user = $this->verificationService->getUserByEmail($email);

        // Account Enumeration Protection: Always return success message
        if ($user && ! $user->is_verified) {
            $token = $this->verificationService->generateToken($user);
            Mail::to($user->email)->send(new VerifyEmail($user, $token));
        }

        return response()->json([
            'message' => 'If this email is registered and not yet verified, a new link has been sent.',
        ]);
    }

    /**
     * Menampilkan halaman landas (landing page) untuk status verifikasi tertunda.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function pending()
    {
        return view('auth.verify-pending');
    }
}
