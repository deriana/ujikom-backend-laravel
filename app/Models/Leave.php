<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Traits\Notificationable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class Leave
 *
 * Model yang merepresentasikan pengajuan cuti oleh karyawan, mencakup detail periode,
 * alasan, lampiran, serta mekanisme persetujuan berjenjang.
 */
class Leave extends Model
{
    use Notifiable, Notificationable, HasFactory;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan yang mengajukan cuti */
        'leave_type_id', /**< ID jenis cuti yang dipilih */
        'date_start', /**< Tanggal mulai cuti */
        'date_end', /**< Tanggal berakhir cuti */
        'reason', /**< Alasan pengambilan cuti */
        'attachment', /**< Path/URL lampiran pendukung */
        'approval_status', /**< Status persetujuan (ApprovalStatus) */
        'is_half_day', /**< Flag apakah cuti hanya setengah hari */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'date_start' => 'date', /**< Konversi tanggal mulai ke objek Carbon */
        'date_end' => 'date', /**< Konversi tanggal akhir ke objek Carbon */
        'is_half_day' => 'boolean', /**< Konversi flag setengah hari ke boolean */
        'approval_status' => 'integer', /**< Konversi status ke integer */
    ];

    /** @var array<int, string> Atribut tambahan yang disertakan dalam serialisasi */
    protected $appends = ['duration', 'duration_text']; /**< Durasi cuti dalam angka dan teks */

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
     * Relasi ke model Employee pemilik pengajuan cuti.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi ke model EmployeeLeave (histori cuti yang sudah sinkron).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function employeeLeave()
    {
        return $this->hasOne(EmployeeLeave::class, 'leave_type_id', 'leave_type_id')
            ->where('employee_id', $this->employee_id);
    }

    /**
     * Relasi ke model LeaveType (Jenis Cuti).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Relasi ke daftar persetujuan (LeaveApproval) untuk pengajuan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    /**
     * Mendapatkan data penyetuju berikutnya berdasarkan level yang masih pending.
     *
     * @return \App\Models\Employee|null
     */
    public function nextApprover()
    {
        return $this->approvals()
            ->where('status', ApprovalStatus::PENDING->value)
            ->orderBy('level')
            ->first()?->approver;
    }

    /**
     * Mengecek apakah pengajuan sudah disetujui oleh Manager (Level 0).
     *
     * @return bool
     */
    public function isApprovedByManager()
    {
        return $this->approvals()
            ->where('level', 0)
            ->where('status', ApprovalStatus::APPROVED->value)
            ->exists();
    }

    /**
     * Mengecek apakah pengajuan sudah disetujui oleh HR (Level 1).
     *
     * @return bool
     */
    public function isApprovedByHR()
    {
        return $this->approvals()
            ->where('level', 1)
            ->where('status', ApprovalStatus::APPROVED->value)
            ->exists();
    }

    /**
     * Scope untuk memfilter pengajuan yang telah disetujui secara final.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', ApprovalStatus::APPROVED->value);
    }

    /**
     * Scope untuk memfilter pengajuan yang masih menunggu persetujuan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::PENDING->value);
    }

    /**
     * Scope untuk memfilter pengajuan yang ditolak.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', ApprovalStatus::REJECTED->value);
    }

    /**
     * Accessor untuk menghitung durasi cuti dalam hari.
     * Menghitung hari kerja dengan mengecualikan akhir pekan dan hari libur nasional.
     * Jika half_day aktif, mengembalikan nilai 0.5.
     *
     * @return float|int
     */
    public function getDurationAttribute()
    {
        if ($this->is_half_day) {
            return 0.5;
        }
        if (! $this->date_start || ! $this->date_end) {
            return 0;
        }

        $holidayDates = Holiday::where(function ($q) {
            $q->whereBetween('start_date', [$this->date_start, $this->date_end])
                ->orWhereBetween('end_date', [$this->date_start, $this->date_end]);
        })
            ->get()
            ->flatMap(function ($holiday) {
                if (! $holiday->end_date || $holiday->start_date->equalTo($holiday->end_date)) {
                    return [$holiday->start_date->format('Y-m-d')];
                }

                return CarbonPeriod::create($holiday->start_date, $holiday->end_date)
                    ->toArray();
            })
            ->map(function ($date) {
                return is_string($date) ? $date : $date->format('Y-m-d');
            })
            ->unique()
            ->toArray();

        $duration = $this->date_start->diffInDaysFiltered(function ($date) use ($holidayDates) {
            return ! $date->isWeekend() && ! in_array($date->format('Y-m-d'), $holidayDates);
        }, $this->date_end) + 1;

        return $duration > 0 ? $duration : 0;
    }

    /**
     * Accessor untuk mendapatkan teks representasi durasi (misal: "3 Days").
     *
     * @return string
     */
    public function getDurationTextAttribute()
    {
        $duration = $this->getDurationAttribute();

        return $duration.($duration > 1 ? ' Days' : ' Day');
    }
}
