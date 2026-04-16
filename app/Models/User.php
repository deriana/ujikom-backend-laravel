<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * Model yang merepresentasikan akun pengguna sistem, menangani autentikasi,
 * peran (roles), serta relasi ke data profil karyawan.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, HasPushSubscriptions;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'name', /**< Nama lengkap pengguna */
        'email', /**< Alamat email untuk login */
        'password', /**< Kata sandi yang di-hash */
        'is_active', /**< Status keaktifan akun */
        'system_reserve', /**< Flag untuk data yang diproteksi sistem */
        'remember_token', /**< Token untuk fitur "remember me" */
        'is_verified', /**< Flag status verifikasi akun */
        'email_verified_at', /**< Waktu saat email diverifikasi */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'password', /**< Kata sandi */
        'remember_token', /**< Token remember me */
        'id', /**< Identifier internal database */
    ];

    /** @var string Nama guard yang digunakan untuk otorisasi Spatie */
    protected $guard_name = 'api'; /**< Menggunakan guard API */

    /**
     * Boot function untuk menangani event model.
     * Digunakan untuk mengotomatisasi pengisian UUID saat pembuatan data
     * dan mencegah penghapusan akun dengan peran Owner.
     *
     * @throws \DomainException Jika mencoba menghapus akun Owner
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });

        static::deleting(function ($model) {
            if ($model->hasRole(\App\Enums\UserRole::OWNER->value)) {
                throw new \DomainException('Akun Owner adalah System Reserved dan tidak dapat dihapus.');
            }
        });
    }

    /**
     * Mendapatkan nama kolom kunci untuk routing Laravel.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Relasi one-to-one ke model Employee.
     * Menghubungkan akun user dengan profil data karyawan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function employee()
    {
        return $this->hasOne(Employee::class)->withTrashed();
    }

    /**
     * Relasi ke model Team melalui model Employee.
     * Mendapatkan tim di mana user (sebagai karyawan) bernaung.
     */
    public function team()
    {
        return $this->hasOneThrough(
            Team::class,
            Employee::class,
            'user_id',
            'id',
            'id',
            'team_id'
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }
}
