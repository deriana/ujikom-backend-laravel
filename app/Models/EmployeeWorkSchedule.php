<?php

namespace App\Models;

use App\Enums\PriorityEnum;
use App\Traits\Blameable;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class EmployeeWorkSchedule
 *
 * Model yang merepresentasikan penugasan jadwal kerja kepada karyawan,
 * mendukung sistem prioritas untuk menentukan jadwal aktif jika terjadi tumpang tindih.
 */
class EmployeeWorkSchedule extends Model
{
    use Blameable, Notifiable, Notificationable, HasFactory;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'uuid', /**< Identifier unik (UUID) */
        'employee_id', /**< ID karyawan yang ditugaskan */
        'work_schedule_id', /**< ID template jadwal kerja */
        'start_date', /**< Tanggal mulai berlakunya jadwal */
        'end_date', /**< Tanggal berakhirnya jadwal (null jika permanen) */
        'priority', /**< Tingkat prioritas jadwal (PriorityEnum) */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'start_date' => 'date', /**< Konversi tanggal mulai ke objek Carbon */
        'end_date' => 'date', /**< Konversi tanggal akhir ke objek Carbon */
        'priority' => PriorityEnum::class, /**< Casting prioritas ke Enum PriorityEnum */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /**
     * Boot function untuk menangani event model.
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
     * Relasi ke model Employee pemilik penugasan jadwal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model WorkSchedule (Template Jadwal).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    /**
     * Scope untuk memfilter jadwal yang aktif pada tanggal tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date Tanggal pengecekan
     */
    public function scopeActiveOn($query, $date)
    {
        return $query->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            });
    }

    /**
     * Relasi ke user yang membuat record penugasan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope untuk memfilter jadwal dengan prioritas Level 1.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeLevel1($query)
    {
        return $query->where('priority', PriorityEnum::LEVEL_1->value);
    }

    /**
     * Scope untuk memfilter jadwal dengan prioritas Level 2.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeLevel2($query)
    {
        return $query->where('priority', PriorityEnum::LEVEL_2->value);
    }

    /**
     * Scope untuk memfilter jadwal berdasarkan level prioritas tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level Nilai prioritas
     */
    public function scopePriority($query, int $level)
    {
        return $query->where('priority', $level);
    }

    /**
     * Mendapatkan satu jadwal aktif terbaik untuk karyawan pada tanggal tertentu.
     *
     * @param int $employeeId ID Karyawan
     * @param string|null $date Tanggal (default: hari ini)
     * @return self|null
     */
    public static function getActiveSchedule($employeeId, $date = null)
    {
        $date = $date ?? now()->toDateString();

        return self::where('employee_id', $employeeId)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
