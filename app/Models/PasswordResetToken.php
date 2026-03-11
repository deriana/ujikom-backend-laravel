<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PasswordResetToken
 *
 * Model yang merepresentasikan token pengaturan ulang kata sandi (password reset),
 * digunakan untuk memvalidasi permintaan pemulihan akun pengguna.
 */
class PasswordResetToken extends Model
{
    /** @var string Nama tabel database yang terkait */
    protected $table = 'password_resets'; /**< Nama tabel kustom untuk penyimpanan token */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'user_id', /**< ID user yang meminta reset password */
        'token', /**< String token unik untuk verifikasi */
        'expires_at', /**< Waktu kedaluwarsa token */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'expires_at' => 'datetime', /**< Konversi waktu kedaluwarsa ke objek Carbon */
    ];

    /**
     * Relasi ke model User pemilik token reset ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mengecek apakah token sudah melewati batas waktu kedaluwarsa.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}
