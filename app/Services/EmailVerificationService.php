<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmailVerificationService
{
    /**
     * Generate a new verification token for the user.
     * Returns the plain-text token.
     */
    public function generateToken(User $user): string
    {
        // Generate cryptographically secure random token (32 bytes = 64 chars in hex)
        $plainToken = bin2hex(random_bytes(32));

        // Store the SHA-256 hash of the token
        VerificationToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token' => hash('sha256', $plainToken),
                'expires_at' => Carbon::now()->addHours(24),
            ]
        );

        return $plainToken;
    }

    /**
     * Verify the token and update the user status in a single transaction.
     */
    public function verifyToken(string $plainToken): bool
    {
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = VerificationToken::where('token', $hashedToken)->first();

        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return false;
        }

        return DB::transaction(function () use ($tokenRecord) {
            $user = $tokenRecord->user;

            $user->update([
                'is_verified' => true,
                'email_verified_at' => Carbon::now(),
            ]);

            // Ensure token is "Single Use"
            $tokenRecord->delete();

            return true;
        });
    }

    /**
     * Get user by email without leaking if it exists (for account enumeration protection).
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function verifyAndSetPassword(string $plainToken, string $newPassword): bool
    {
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = VerificationToken::where('token', $hashedToken)->first();

        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return false;
        }

        return DB::transaction(function () use ($tokenRecord, $newPassword) {
            $user = $tokenRecord->user;

            $user->update([
                'password' => Hash::make($newPassword),
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);

            // Hapus token setelah dipakai
            $tokenRecord->delete();

            return true;
        });
    }

    public function checkToken(string $plainToken): ?User
    {
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = VerificationToken::where('token', $hashedToken)->first();

        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return null;
        }

        return $tokenRecord->user;
    }
}
