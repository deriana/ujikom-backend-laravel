<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordResetToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    /**
     * Generate a new password reset token for the user.
     * Returns the plain-text token.
     */
    public function generateToken(User $user): string
    {
        // Generate cryptographically secure random token (32 bytes = 64 chars in hex)
        $plainToken = bin2hex(random_bytes(32));

        // Store the SHA-256 hash of the token
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
     */
    public function checkToken(string $plainToken): ?User
    {
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = PasswordResetToken::where('token', $hashedToken)->first();

        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return null;
        }

        return $tokenRecord->user;
    }

    /**
     * Reset the user's password and delete the token in a transaction.
     */
    public function resetPassword(string $plainToken, string $newPassword): bool
    {
        $hashedToken = hash('sha256', $plainToken);
        $tokenRecord = PasswordResetToken::where('token', $hashedToken)->first();

        if (! $tokenRecord || $tokenRecord->isExpired()) {
            return false;
        }

        return DB::transaction(function () use ($tokenRecord, $newPassword) {
            $user = $tokenRecord->user;

            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // Ensure token is "Single Use"
            $tokenRecord->delete();

            return true;
        });
    }

    /**
     * Get user by email for account enumeration protection.
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
