<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\Notificationable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Class Payroll
 *
 * Model yang merepresentasikan data penggajian (payroll) karyawan untuk periode tertentu,
 * mencakup perhitungan gaji pokok, tunjangan, lembur, bonus, serta potongan.
 */
class Payroll extends Model
{
    use Blameable, Notifiable, Notificationable, HasFactory;

    /** @var array Konfigurasi notifikasi kustom */
    public $customNotification = []; /**< Pengaturan notifikasi khusus untuk model ini */

    /** @var bool Status apakah melewati notifikasi default */
    public $skipDefaultNotification = true; /**< Flag untuk menonaktifkan notifikasi standar Laravel */

    const STATUS_DRAFT = 0; /**< Konstanta status draf (belum final) */

    const STATUS_FINALIZED = 1; /**< Konstanta status final (sudah disetujui) */

    const STATUS_VOIDED = 2; /**< Konstanta status dibatalkan (void) */

    /** @var array<int, string> Atribut yang dapat diisi secara massal */
    protected $fillable = [
        'employee_id', /**< ID karyawan pemilik data payroll */
        'period_start', /**< Tanggal mulai periode penggajian */
        'period_end', /**< Tanggal berakhir periode penggajian */
        'base_salary', /**< Nominal gaji pokok */
        'allowance_total', /**< Total nominal tunjangan */
        'overtime_pay', /**< Total nominal upah lembur */
        'assessment_bonus', /**< Bonus berdasarkan penilaian kinerja */
        'manual_adjustment', /**< Penyesuaian nominal secara manual */
        'adjustment_note', /**< Catatan alasan penyesuaian manual */
        'gross_salary', /**< Total gaji kotor sebelum potongan */
        'status', /**< Status payroll (Draft, Finalized, Voided) */
        'finalized_at', /**< Waktu saat payroll difinalisasi */
        'late_deduction', /**< Potongan akibat keterlambatan */
        'early_leave_deduction', /**< Potongan akibat pulang awal */
        'total_deduction', /**< Total seluruh potongan */
        'net_salary', /**< Gaji bersih yang diterima karyawan */
        'tax_amount', /**< Nominal potongan pajak (PPh21) */
        'taxable_income', /**< Penghasilan Kena Pajak (PKP) */
        'tax_rate', /**< Tarif pajak yang dikenakan */
        'ptkp', /**< Penghasilan Tidak Kena Pajak (PTKP) */
        'slip_path', /**< Path/URL file slip gaji (PDF) */
        'is_void', /**< Flag apakah payroll dibatalkan */
        'void_note', /**< Alasan pembatalan payroll */
        'slip_generated_at', /**< Waktu saat file slip gaji dibuat */
        'updated_by_id', /**< ID user pengubah terakhir */
        'created_by_id', /**< ID user pembuat record */
    ];

    /** @var array<string, string> Casting tipe data atribut */
    protected $casts = [
        'period_start' => 'date', /**< Konversi mulai periode ke objek Carbon */
        'period_end' => 'date', /**< Konversi akhir periode ke objek Carbon */
        'is_void' => 'boolean', /**< Konversi flag void ke boolean */
        'base_salary' => 'decimal:2', /**< Konversi gaji pokok ke desimal */
        'allowance_total' => 'decimal:2', /**< Konversi total tunjangan ke desimal */
        'overtime_pay' => 'decimal:2', /**< Konversi upah lembur ke desimal */
        'manual_adjustment' => 'decimal:2', /**< Konversi penyesuaian ke desimal */
        'gross_salary' => 'decimal:2', /**< Konversi gaji kotor ke desimal */
        'late_deduction' => 'decimal:2', /**< Konversi potongan telat ke desimal */
        'early_leave_deduction' => 'decimal:2', /**< Konversi potongan pulang awal ke desimal */
        'total_deduction' => 'decimal:2', /**< Konversi total potongan ke desimal */
        'net_salary' => 'decimal:2', /**< Konversi gaji bersih ke desimal */
        'tax_amount' => 'decimal:2', /**< Konversi nominal pajak ke desimal */
        'taxable_income' => 'decimal:2', /**< Konversi PKP ke desimal */
        'tax_rate' => 'decimal:2', /**< Konversi tarif pajak ke desimal */
        'ptkp' => 'decimal:2', /**< Konversi PTKP ke desimal */
        'status' => 'integer', /**< Konversi status ke integer */
        'finalized_at' => 'datetime', /**< Konversi waktu finalisasi ke objek Carbon */
        'slip_generated_at' => 'datetime', /**< Konversi waktu generate slip ke objek Carbon */
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

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    public function scopeForPeriod(Builder $query, $start, $end): Builder
    {
        return $query->where('period_start', $start)
            ->where('period_end', $end);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isFinalized(): bool
    {
        return $this->status == self::STATUS_FINALIZED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED || (bool) $this->is_void;
    }

    public function getStatusLabel(): string
    {
        if ($this->isVoided()) {
            return 'Voided';
        }
        if ($this->isFinalized()) {
            return 'Finalized';
        }

        return 'Draft';
    }

    /**
     * Mengecek apakah data payroll masih dapat diubah.
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->isDraft() && ! $this->isVoided();
    }

    /**
     * Mengubah status payroll menjadi final dan mencatat waktu finalisasi.
     *
     * @return void
     */
    public function finalize(): void
    {
        $this->update([
            'status' => self::STATUS_FINALIZED,
            'finalized_at' => now(),
        ]);
    }

    /**
     * Membatalkan data payroll (void) dengan menyertakan alasan.
     *
     * @param string $note Alasan pembatalan
     * @return void
     */
    public function void(string $note): void
    {
        $this->update([
            'status' => self::STATUS_VOIDED,
            'is_void' => true,
            'void_note' => $note,
        ]);
    }
}
