<?php

namespace App\Enums;

/**
 * Enum EmployeeStatus
 *
 * Mendefinisikan status kepegawaian atau jenis kontrak karyawan.
 */
enum EmployeeStatus:int
{
    case PERMANENT = 0; /**< Status karyawan tetap */
    case CONTRACT = 1;  /**< Status karyawan kontrak */
    case INTERN = 2;    /**< Status karyawan magang */
    case PROBATION = 3; /**< Status karyawan masa percobaan */

    /**
     * Mendapatkan label deskriptif untuk setiap status kepegawaian.
     *
     * @return string Label status kepegawaian
     */
    public function label(): string
    {
        return match($this) {
            self::PERMANENT => 'Permanent',
            self::CONTRACT => 'Contract',
            self::INTERN => 'Intern',
            self::PROBATION => 'Probation',
        };
    }
}
