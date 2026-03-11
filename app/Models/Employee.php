<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Traits\Blameable;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class Employee
 *
 * Model yang merepresentasikan data profil lengkap karyawan, mencakup informasi personal,
 * status kepegawaian, penggajian, serta relasi ke manajemen dan struktur organisasi.
 */
class Employee extends Model implements HasMedia
{
    use Blameable, InteractsWithMedia, Notifiable, Notificationable, SoftDeletes, HasFactory;

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'nik', /**< Nomor Induk Karyawan unik */
        'user_id', /**< ID user yang terhubung untuk login */
        'team_id', /**< ID tim tempat karyawan bernaung */
        'position_id', /**< ID jabatan karyawan */
        'manager_id', /**< ID karyawan yang menjadi atasan langsung */
        'employee_status', /**< Status kepegawaian (Permanent, Contract, dll) */
        'contract_start', /**< Tanggal mulai kontrak */
        'contract_end', /**< Tanggal berakhir kontrak */
        'base_salary', /**< Gaji pokok karyawan */
        'phone', /**< Nomor telepon kontak */
        'gender', /**< Jenis kelamin */
        'date_of_birth', /**< Tanggal lahir */
        'address', /**< Alamat tempat tinggal */
        'join_date', /**< Tanggal mulai bergabung di perusahaan */
        'resign_date', /**< Tanggal pengunduran diri */
        'created_by_id', /**< ID user pembuat record */
        'updated_by_id', /**< ID user pengubah terakhir */
        'deleted_by_id', /**< ID user penghapus record */
        'employment_state', /**< Status aktif/non-aktif secara sistem */
        'termination_date', /**< Tanggal pemutusan hubungan kerja */
        'termination_reason', /**< Alasan pemutusan hubungan kerja */
    ];

    /** @var array<int, string> Atribut yang disembunyikan dari serialisasi JSON */
    protected $hidden = [
        'id', /**< Identifier internal database */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'base_salary' => 'decimal:2', /**< Konversi gaji ke format desimal */
        'employee_status' => EmployeeStatus::class, /**< Casting status ke Enum EmployeeStatus */
        'join_date' => 'date', /**< Konversi tanggal gabung ke objek Carbon */
        'contract_start' => 'date', /**< Konversi mulai kontrak ke objek Carbon */
        'contract_end' => 'date', /**< Konversi akhir kontrak ke objek Carbon */
        'resign_date' => 'date', /**< Konversi tanggal resign ke objek Carbon */
        'date_of_birth' => 'date', /**< Konversi tanggal lahir ke objek Carbon */
        'termination_date' => 'date', /**< Konversi tanggal terminasi ke objek Carbon */
    ];

    /**
     * Boot function untuk menangani event model.
     * Mengotomatisasi pembuatan NIK dan inisialisasi saldo cuti saat karyawan baru dibuat.
     */
    protected static function booted()
    {
        static::creating(function ($employee) {
            if (empty($employee->nik)) {
                $employee->nik = self::generateNik();
            }
        });
        static::created(function ($employee) {

            $currentYear = now()->year;

            $leaveTypes = LeaveType::whereNotNull('default_days')
                ->where('is_active', true)
                ->get();

            foreach ($leaveTypes as $type) {
                EmployeeLeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $currentYear,
                    ],
                    [
                        'total_days' => $type->default_days,
                        'used_days' => 0,
                        // 'remaining_days' => $type->default_days,
                    ]
                );
            }
        });

    }

    /**
     * Menghasilkan NIK (Nomor Induk Karyawan) secara otomatis berdasarkan tahun saat ini.
     *
     * @return string NIK dengan format EMP[YYYY][4-digit-sequence]
     */
    public static function generateNik(): string
    {
        $year = now()->format('Y');

        $last = self::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $number = $last ? ((int) substr($last->nik, -4)) + 1 : 1;

        return 'EMP'.$year.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mendaftarkan koleksi media untuk Spatie Media Library.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photo')->singleFile();
    }

    /**
     * Relasi ke user yang membuat record karyawan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relasi ke model User (akun login).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model Team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relasi ke model Position (Jabatan).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Relasi ke model Employee sebagai atasan langsung (manager).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Relasi ke model Employee sebagai bawahan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Scope untuk memfilter karyawan yang masih aktif (belum resign).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('resign_date');
    }

    /**
     * Scope untuk memfilter karyawan dengan status tetap (Permanent).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePermanent($query)
    {
        return $query->where('employee_status', EmployeeStatus::PERMANENT);
    }

    /**
     * Mengecek apakah karyawan masih aktif bekerja.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return is_null($this->resign_date);
    }

    /**
     * Mengecek apakah karyawan berstatus kontrak.
     *
     * @return bool
     */
    public function isContract(): bool
    {
        return $this->employee_status === EmployeeStatus::CONTRACT;
    }

    /**
     * Mengecek apakah karyawan berstatus tetap.
     *
     * @return bool
     */
    public function isPermanent(): bool
    {
        return $this->employee_status === EmployeeStatus::PERMANENT;
    }

    /**
     * Mengecek apakah masa kontrak karyawan telah berakhir.
     *
     * @return bool
     */
    public function hasContractEnded(): bool
    {
        return $this->contract_end && now()->gt($this->contract_end);
    }

    /**
     * Relasi ke data biometrik wajah karyawan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function biometrics()
    {
        return $this->hasMany(BiometricUser::class);
    }

    /**
     * Accessor untuk mengecek apakah karyawan sudah memiliki data wajah terdaftar.
     *
     * @return bool
     */
    public function getHasFaceDescriptorAttribute(): bool
    {
        return $this->biometrics()->exists();
    }

    /**
     * Relasi ke data presensi (Attendance).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relasi ke penugasan jadwal kerja (EmployeeWorkSchedule).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workSchedules()
    {
        return $this->hasMany(EmployeeWorkSchedule::class);
    }

    /**
     * Relasi ke penugasan shift (EmployeeShift).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * Relasi ke pengajuan cuti (Leave).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Relasi ke persetujuan cuti yang dilakukan oleh karyawan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leaveApprovals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    /**
     * Relasi ke data histori cuti karyawan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employeeLeaves()
    {
        return $this->hasMany(EmployeeLeave::class);
    }

    /**
     * Relasi ke saldo cuti tahunan karyawan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class, 'employee_id');
    }

    /**
     * Relasi ke data lembur (Overtime).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    /**
     * Relasi ke penilaian di mana karyawan ini sebagai pihak yang dinilai.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'evaluatee_id');
    }

    /**
     * Relasi ke penilaian di mana karyawan ini bertindak sebagai penilai.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assessmentsAsEvaluator()
    {
        return $this->hasMany(Assessment::class, 'evaluator_id');
    }

    /**
     * Relasi ke penilaian di mana karyawan ini sebagai pihak yang dinilai (alias).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assessmentsAsEvaluatee()
    {
        return $this->hasMany(Assessment::class, 'evaluatee_id');
    }

    /**
     * Mendapatkan nama kolom kunci untuk routing Laravel (menggunakan NIK).
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'nik';
    }

    /**
     * Mendapatkan jadwal kerja yang aktif pada tanggal tertentu.
     *
     * @param string|null $date Tanggal pengecekan (default: hari ini)
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function activeWorkSchedule($date = null)
    {
        $date = $date ?? now()->toDateString();

        return $this->hasOne(EmployeeWorkSchedule::class)
            ->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->with('workSchedule.workMode');
    }

    /**
     * Accessor untuk mendapatkan label teks dari status kepegawaian.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->employee_status) {
            EmployeeStatus::PERMANENT => 'Permanent',
            EmployeeStatus::CONTRACT => 'Contract',
            EmployeeStatus::INTERN => 'Intern',
            EmployeeStatus::PROBATION => 'Probation',
        };
    }
}
