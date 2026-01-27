<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case HR = 'hr';
    case MANAGER = 'manager';
    case FINANCE = 'finance';
    case EMPLOYEE = 'employee';
    case INTERN = 'intern';

    /**
     * Opsional: Menampilkan label yang lebih rapi untuk UI
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN    => 'Administrator',
            self::HR        => 'Human Resource',
            self::MANAGER     => 'Management / Manager',
            self::FINANCE     => 'Finance & Payroll',
            self::EMPLOYEE    => 'Regular Employee',
            self::INTERN     => 'Internship / Magang',
        };
    }

    /**
     * Penjelasan hierarki dan tanggung jawab masing-masing role
     */
    public function description(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Pemegang kekuasaan tertinggi dalam sistem dengan akses penuh ke seluruh konfigurasi teknis dan data.',
            self::ADMIN       => 'Pengelola operasional sistem harian, bertanggung jawab atas manajemen pengguna dan pemeliharaan data.',
            self::HR          => 'Bertanggung jawab atas manajemen sumber daya manusia, termasuk data karyawan dan kebijakan internal.',
            self::MANAGER     => 'Pemimpin tim atau departemen yang memiliki wewenang untuk menyetujui pengajuan dan memantau kinerja.',
            self::FINANCE     => 'Mengelola aspek keuangan perusahaan, terutama terkait penggajian (payroll) dan klaim biaya.',
            self::EMPLOYEE    => 'Karyawan tetap yang menggunakan sistem untuk absensi dan pengajuan administrasi harian.',
            self::INTERN      => 'Peserta magang dengan akses terbatas yang hanya mencakup fungsi dasar operasional.',
        };
    }
}
