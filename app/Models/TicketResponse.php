<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class TicketResponse
 *
 * Model yang merepresentasikan balasan atau tanggapan pada sebuah tiket,
 * baik dari pengguna (helpdesk) maupun balasan otomatis.
 */
class TicketResponse extends Model
{
    use HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'ticket_id', /**< ID tiket yang direspon */
        'responder_id', /**< ID user (helpdesk/admin) yang merespon */
        'response', /**< Isi tanggapan atau balasan */
        'is_auto_reply', /**< Penanda apakah ini adalah balasan otomatis */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'is_auto_reply' => 'boolean',
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /**
     * Boot function untuk menangani event model.
     * Digunakan untuk mengotomatisasi pengisian UUID saat pembuatan data.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
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
     * Relasi ke model Ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relasi ke model User yang bertindak sebagai responder.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
