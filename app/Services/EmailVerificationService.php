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
        // 1. Generate cryptographically secure random token (32 bytes = 64 chars in hex)
        $plainToken = bin2hex(random_bytes(32));

        // 2. Store the SHA-256 hash of the token with 24 hours expiration
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
     *
     * @param string $plainToken
     * @return bool
     */
    public function verifyToken(string $plainToken): bool
    {
        // 1. Hash the incoming plain token and find the record
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = VerificationToken::where('token', $hashedToken)->first();

        // 2. Validate token existence and expiration
        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return false;
        }

        return DB::transaction(function () use ($tokenRecord) {
            // 3. Update user verification status
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
     *
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Verify the token, set a new password, and mark user as verified.
     *
     * @param string $plainToken
     * @param string $newPassword
     * @return bool
     */
    public function verifyAndSetPassword(string $plainToken, string $newPassword): bool
    {
        // 1. Hash the incoming plain token and find the record
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = VerificationToken::where('token', $hashedToken)->first();

        // 2. Validate token existence and expiration
        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return false;
        }

        return DB::transaction(function () use ($tokenRecord, $newPassword) {
            // 3. Update user password and verification status
            $user = $tokenRecord->user;

            $user->update([
                'password' => Hash::make($newPassword),
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);

            // 4. Delete token after successful use
            $tokenRecord->delete();

            return true;
        });
    }

    /**
     * Check if a token is valid and return the associated user.
     *
     * @param string $plainToken
     * @return User|null
     */
    public function checkToken(string $plainToken): ?User
    {
        // 1. Hash the incoming plain token and find the record
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = VerificationToken::where('token', $hashedToken)->first();

        // 2. Validate token existence and expiration
        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return null;
        }

        return $tokenRecord->user;
    }
}
