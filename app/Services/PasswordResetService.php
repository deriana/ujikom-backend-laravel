<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordResetToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Service class to handle password reset logic including token generation and validation.
 */
class PasswordResetService
{
    /**
     * Generate a new password reset token for the user.
     *
     * @param User $user
     * @return string The plain-text token
     */
    public function generateToken(User $user): string
    {
        // 1. Generate cryptographically secure random token (32 bytes = 64 chars in hex)
        $plainToken = bin2hex(random_bytes(32));

        // 2. Store the SHA-256 hash of the token with a 1-hour expiration
        PasswordResetToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token' => hash('sha256', $plainToken),
                'expires_at' => Carbon::now()->addHour(), // 1 hour expiration
            ]
        );

        return $plainToken;
    }

    /**
     * Check if the token is valid and return the associated user.
     *
     * @param string $plainToken
     * @return User|null
     */
    public function checkToken(string $plainToken): ?User
    {
        // 1. Hash the incoming plain token to match stored format
        $hashedToken = hash('sha256', $plainToken);

        // 2. Retrieve the token record from the database
        $tokenRecord = PasswordResetToken::where('token', $hashedToken)->first();

        // 3. Validate existence and expiration status
        if (!$tokenRecord || $tokenRecord->isExpired()) {
            return null;
        }

        return $tokenRecord->user;
    }

    /**
     * Reset the user's password and delete the token in a transaction.
     *
     * @param string $plainToken
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword(string $plainToken, string $newPassword): bool
    {
        // 1. Hash the token and find the record
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = PasswordResetToken::where('token', $hashedToken)->first();

        // 2. Validate token validity
        if (!$tokenRecord || $tokenRecord->isExpired()) {
            return false;
        }

        return DB::transaction(function () use ($tokenRecord, $newPassword) {
            // 3. Update the user's password with a new hash
            $user = $tokenRecord->user;
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // 4. Delete the token to ensure it is single-use only
            $tokenRecord->delete();

            return true;
        });
    }

    /**
     * Get user by email for account enumeration protection.
     *
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        // 1. Find user by email address
        return User::where('email', $email)->first();
    }
}
