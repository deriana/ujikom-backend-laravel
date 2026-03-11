<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class DivisionTeamEmployeeResource
 *
 * Resource class untuk mentransformasi model Division menjadi format JSON yang mencakup
 * struktur hierarki lengkap dari Divisi, Tim, hingga detail Karyawan.
 */
class DivisionTeamEmployeeResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid, /**< Identifier unik divisi */
            'division_name' => $this->name, /**< Nama divisi */
            'division_code' => $this->code, /**< Kode unik divisi */

            'teams' => $this->teams->map(function ($team) {
                return [ /**< Daftar tim di bawah divisi ini */
                    'uuid' => $team->uuid, /**< Identifier unik tim */
                    'team_name' => $team->name, /**< Nama tim */

                    // Transformasi Anggota dengan data Employee penting
                    'members' => $team->employees->map(function ($employee) {
                        $user = $employee->user; /**< Relasi user dari karyawan */
                        if (! $user) {
                            return null;
                        }

                        return [ /**< Detail data karyawan (anggota tim) */
                            'nik' => $employee->nik, /**< Nomor Induk Karyawan */
                            'name' => $user->name, /**< Nama lengkap karyawan */
                            'email' => $user->email, /**< Alamat email */
                            'phone' => $employee->phone, /**< Nomor telepon */
                            // Data Jabatan & Status
                            'position' => $employee->position?->name ?? 'No Position', /**< Nama jabatan */
                            'status' => [
                                'label' => $employee->status_label, /**< Label status (dari accessor) */
                                'type' => $employee->employee_status, /**< Nilai enum status */
                                'is_active' => $employee->isActive(), /**< Status keaktifan saat ini */
                            ],
                            // Data Kontrak
                            'employment' => [
                                'join_date' => $employee->join_date?->format('d M Y'), /**< Tanggal bergabung */
                                'years_of_service' => $employee->join_date?->diffInYears(now()), /**< Masa kerja dalam tahun */
                                'contract_due' => $employee->contract_end?->format('d M Y'), /**< Tanggal berakhir kontrak */
                                'is_contract_ended' => $employee->hasContractEnded(), /**< Status apakah kontrak sudah berakhir */
                            ],
                            // Media / Avatar
                            'avatar' => $employee->getFirstMediaUrl('profile_photo') ?? null, /**< URL foto profil */
                        ];
                    })->filter()->values(),

                    'total_members' => $team->employees->count(), /**< Jumlah anggota dalam satu tim */
                ];
            }),

            'stats' => [
                'total_teams' => $this->teams->count(), /**< Total jumlah tim dalam divisi */
                'total_employees' => $this->teams->sum(fn ($team) => $team->employees->count()), /**< Total jumlah seluruh karyawan dalam divisi */
            ],
        ];
    }
}
