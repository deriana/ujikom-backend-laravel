<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class VerificationToken
 *
 * Model yang merepresentasikan token verifikasi akun pengguna, digunakan untuk
 * proses aktivasi email atau validasi identitas pengguna.
 */
class VerificationToken extends Model
{
    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'user_id', /**< ID user yang terkait dengan token ini */
        'token', /**< String token unik untuk verifikasi */
        'expires_at', /**< Waktu kedaluwarsa token */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'expires_at' => 'datetime', /**< Konversi waktu kedaluwarsa ke objek Carbon */
    ];

    /**
     * Relasi ke model User pemilik token verifikasi ini.
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
