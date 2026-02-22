<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class VerificationController extends Controller
{
    protected $verificationService;

    public function __construct(EmailVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Handle the verification link click.
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
     * Check if the token is valid.
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
     * Handle resending the verification email.
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
     * Verification Pending landing page.
     */
    public function pending()
    {
        return view('auth.verify-pending');
    }
}
