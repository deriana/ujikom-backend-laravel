<?php

namespace App\Enums;

/**
 * Enum EmployeeState
 *
 * Mendefinisikan status keberadaan atau kondisi kerja karyawan dalam sistem.
 */
enum EmployeeState: string
{
    case ACTIVE = 'active'; /**< Status karyawan yang masih aktif bekerja */
    case RESIGNED = 'resigned'; /**< Status karyawan yang telah mengundurkan diri */
    case TERMINATED = 'terminated'; /**< Status karyawan yang telah diberhentikan */

    /**
     * Mendapatkan label deskriptif dalam Bahasa Indonesia untuk setiap status karyawan.
     *
     * @return string Label status karyawan
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::RESIGNED => 'Mengundurkan Diri',
            self::TERMINATED => 'Diberhentikan',
        };
    }
}
