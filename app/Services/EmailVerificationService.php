<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Class EmailVerificationService
 *
 * Menangani logika verifikasi email pengguna, termasuk pembuatan token,
 * validasi token, dan pengaturan kata sandi saat verifikasi.
 */
class EmailVerificationService
{
    /**
     * Membuat token verifikasi baru untuk pengguna.
     *
     * @param User $user Objek pengguna.
     * @return string Token dalam format teks biasa (plain-text).
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
     * Memverifikasi token dan memperbarui status verifikasi pengguna dalam satu transaksi.
     *
     * @param string $plainToken Token teks biasa yang akan diverifikasi.
     * @return bool True jika verifikasi berhasil, false jika token tidak valid atau kedaluwarsa.
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
     * Mengambil data pengguna berdasarkan alamat email.
     *
     * @param string $email Alamat email pengguna.
     * @return User|null Objek pengguna jika ditemukan, atau null.
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Memverifikasi token, menetapkan kata sandi baru, dan menandai pengguna sebagai terverifikasi.
     *
     * @param string $plainToken Token teks biasa.
     * @param string $newPassword Kata sandi baru yang akan disimpan.
     * @return bool True jika berhasil, false jika gagal.
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
     * Memeriksa apakah token valid dan mengembalikan pengguna yang terkait.
     *
     * @param string $plainToken Token teks biasa.
     * @return User|null Objek pengguna jika token valid, atau null.
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
