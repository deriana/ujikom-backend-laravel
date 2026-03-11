<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordResetToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Class PasswordResetService
 *
 * Menangani logika pengaturan ulang kata sandi termasuk pembuatan dan validasi token.
 */
class PasswordResetService
{
    /**
     * Membuat token pengaturan ulang kata sandi baru untuk pengguna.
     *
     * @param User $user Objek pengguna.
     * @return string Token dalam format teks biasa (plain-text).
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
     * Memeriksa apakah token valid dan mengembalikan pengguna yang terkait.
     *
     * @param string $plainToken Token teks biasa.
     * @return User|null Objek pengguna jika token valid, atau null.
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
     * Mengatur ulang kata sandi pengguna dan menghapus token dalam satu transaksi.
     *
     * @param string $plainToken Token teks biasa.
     * @param string $newPassword Kata sandi baru yang akan disimpan.
     * @return bool True jika berhasil, false jika token tidak valid atau kedaluwarsa.
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
     * Mengambil data pengguna berdasarkan alamat email.
     *
     * @param string $email Alamat email pengguna.
     * @return User|null Objek pengguna jika ditemukan, atau null.
     */
    public function getUserByEmail(string $email): ?User
    {
        // 1. Find user by email address
        return User::where('email', $email)->first();
    }
}
