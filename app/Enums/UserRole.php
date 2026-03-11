<?php

namespace App\Enums;

/**
 * Enum UserRole
 *
 * Mendefinisikan berbagai peran pengguna (role) yang tersedia dalam sistem.
 */
enum UserRole: string
{
    case ADMIN = 'admin'; /**< Peran Administrator dengan akses manajemen sistem */
    case DIRECTOR = 'director'; /**< Peran Direktur untuk pengawasan tingkat tinggi */
    case OWNER = 'owner'; /**< Peran Pemilik dengan hak akses penuh/tertinggi */
    case MANAGER = 'manager'; /**< Peran Manajer untuk persetujuan dan manajemen tim */
    case HR = 'hr'; /**< Peran Human Resources untuk manajemen SDM dan payroll */
    case FINANCE = 'finance'; /**< Peran Keuangan untuk pengelolaan transaksi dan gaji */
    case EMPLOYEE = 'employee'; /**< Peran Karyawan standar untuk akses fitur mandiri */
}
