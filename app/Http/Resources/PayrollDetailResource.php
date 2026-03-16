<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PayrollDetailResource
 *
 * Resource class untuk mentransformasi detail model Payroll menjadi format JSON yang komprehensif,
 * mencakup rincian gaji, tunjangan, potongan, perhitungan pajak, dan informasi karyawan.
 */
class PayrollDetailResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi detail slip gaji yang mendalam.
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employee;
        $position = $employee?->position;
        $periodString = $this->period_start?->format('Y-m');

        // Hitung bonus dari relasi assessmentsAsEvaluatee
        $calculatedBonus = $employee?->assessmentsAsEvaluatee
            ->filter(fn($a) => str_starts_with($a->period, $periodString))
            ->flatMap(fn($a) => $a->assessments_details)
            ->sum('bonus_salary') ?? 0;

        return [
            /*
            |--------------------------------------------------------------------------
            | Payroll Metadata
            |--------------------------------------------------------------------------
            */
            'uuid' => $this->uuid, /**< Identifier unik record payroll */
            'status' => [
                'code' => $this->status, /**< Kode status payroll (enum/integer) */
                'label' => $this->getStatusLabel(), /**< Label status yang mudah dibaca */
                'is_editable' => $this->isEditable(), /**< Status apakah data masih dapat diubah */
            ],
            'finalized_at' => $this->finalized_at,

            /*
            |--------------------------------------------------------------------------
            | Employee Information
            |--------------------------------------------------------------------------
            */
            'employee' => [
                'nik' => $employee?->nik, /**< Nomor Induk Karyawan */
                'name' => $employee?->user?->name, /**< Nama lengkap karyawan */
                'phone' => $employee?->phone, /**< Nomor telepon karyawan */
                'employment_status' => $employee?->employee_status?->label(), /**< Status kepegawaian (Tetap/Kontrak) */
                'join_date' => $employee?->join_date, /**< Tanggal bergabung karyawan */
                'position' => [
                    'name' => $position?->name, /**< Nama jabatan */
                    'base_salary_position' => $position?->base_salary, /**< Gaji pokok standar jabatan */
                ],
                'profile_photo' => $employee?->getFirstMediaUrl('profile_photo') ?: null, /**< URL foto profil */
            ],

            /*
            |--------------------------------------------------------------------------
            | Period
            |--------------------------------------------------------------------------
            */
            'period' => [
                'start' => $this->period_start?->format('Y-m-d'), /**< Tanggal mulai periode penggajian */
                'end' => $this->period_end?->format('Y-m-d'), /**< Tanggal berakhir periode penggajian */
                'days' => $this->period_start && $this->period_end
                    ? $this->period_start->diffInDays($this->period_end) + 1
                    : null, /**< Total jumlah hari dalam periode */
            ],

            /*
            |--------------------------------------------------------------------------
            | Earnings (Pendapatan)
            |--------------------------------------------------------------------------
            */
            'earnings' => [
                'base_salary' => $this->base_salary, /**< Gaji pokok yang diterima */

                'allowances' => $position?->allowances?->map(function ($allowance) {
                    return [
                        'name' => $allowance->name, /**< Nama tunjangan */
                        'type' => $allowance->type, /**< Tipe tunjangan */
                        'amount' => $allowance->pivot->amount ?? $allowance->amount, /**< Nominal tunjangan */
                    ];
                })->values(),

                'allowance_total' => $this->allowance_total, /**< Total seluruh tunjangan */
                'overtime_pay' => $this->overtime_pay, /**< Total upah lembur */
                'assessment_bonus' => $this->assessment_bonus ?? $calculatedBonus, /**< Bonus berdasarkan penilaian kinerja */
                'manual_adjustment' => $this->manual_adjustment, /**< Penyesuaian nominal secara manual */

                'gross_salary' => $this->gross_salary, /**< Total gaji kotor (sebelum potongan) */
            ],

            /*
            |--------------------------------------------------------------------------
            | Deductions (Potongan)
            |--------------------------------------------------------------------------
            */
            'deductions' => [
                'late_deduction' => $this->late_deduction, /**< Potongan karena keterlambatan */
                'early_leave_deduction' => $this->early_leave_deduction, /**< Potongan karena pulang awal */
                'total_attendance_deduction' => ($this->late_deduction ?? 0) +
                    ($this->early_leave_deduction ?? 0), /**< Total potongan terkait absensi */

                'tax_amount' => $this->tax_amount, /**< Nominal potongan pajak (PPh21) */

                'total_deduction' => $this->total_deduction, /**< Total seluruh potongan */
            ],

            /*
            |--------------------------------------------------------------------------
            | Tax Summary
            |--------------------------------------------------------------------------
            */
            'tax_summary' => [
                'ptkp' => $this->ptkp, /**< Penghasilan Tidak Kena Pajak */
                'taxable_income' => $this->taxable_income, /**< Penghasilan Kena Pajak (PKP) */
                'tax_rate_percent' => $this->tax_rate, /**< Persentase tarif pajak yang dikenakan */
                'tax_rate_decimal' => $this->tax_rate
                    ? $this->tax_rate / 100
                    : null, /**< Tarif pajak dalam format desimal */
                'tax_amount' => $this->tax_amount, /**< Total nominal pajak */
            ],

            /*
            |--------------------------------------------------------------------------
            | Final Calculation
            |--------------------------------------------------------------------------
            */
            'summary' => [
                'gross_salary' => $this->gross_salary, /**< Total pendapatan kotor */
                'total_deduction' => $this->total_deduction, /**< Total seluruh potongan */
                'net_salary' => $this->net_salary, /**< Gaji bersih yang diterima (Take Home Pay) */
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes & Audit
            |--------------------------------------------------------------------------
            */
            'adjustment_note' => $this->adjustment_note, /**< Catatan alasan penyesuaian manual */
            'created_at' => $this->created_at, /**< Waktu pembuatan record */
            'updated_at' => $this->updated_at, /**< Waktu pembaruan terakhir */
        ];
    }
}
