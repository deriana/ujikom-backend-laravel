<?php

namespace App\Models;

use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class Ticket
 *
 * Model yang merepresentasikan tiket bantuan dari karyawan, mencatat subjek,
 * deskripsi masalah, serta status dan prioritas tiket.
 */
class Ticket extends Model
{
    use Notifiable, Notificationable, HasFactory;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'reporter_id', /**< ID karyawan yang melaporkan (membuat tiket) */
        'operator_id', /**< ID pengguna/helpdesk yang menangani tiket */
        'subject', /**< Subjek atau judul tiket */
        'description', /**< Deskripsi detail mengenai laporan tiket */
        'priority', /**< Prioritas tiket (low, mid, high) */
        'status', /**< Status tiket (open, in progress, closed) */
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
     * Relasi ke model Employee yang bertindak sebagai pelapor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reporter_id');
    }

    /**
     * Relasi ke model User yang bertindak sebagai operator/helpdesk penanganan tiket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Scope untuk memfilter tiket yang masih berstatus 'open'.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope untuk memfilter tiket yang sedang dalam penanganan ('in progress').
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in progress');
    }

    /**
     * Scope untuk memfilter tiket yang sudah ditutup ('closed').
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope untuk memfilter tiket yang sudah ditutup ('resolved').
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Relasi ke model TicketResponse (Balasan Tiket).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function responses()
    {
        return $this->hasMany(TicketResponse::class, 'ticket_id');
    }

    /**
     * Relasi ke model SatisfactionRating (Penilaian Kepuasan).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function rating()
    {
        return $this->hasOne(SatisfactionRating::class, 'ticket_id');
    }
}
