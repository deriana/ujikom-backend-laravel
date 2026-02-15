<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creatorId = User::first()->id;

        $leaveTypes = [
            [
                'name' => 'Cuti Tahunan',
                // 'description' => 'Hak cuti rutin tahunan karyawan.',
                'is_active' => true,
                'default_days' => 12,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
            [
                'name' => 'Cuti Sakit',
                // 'description' => 'Cuti karena gangguan kesehatan (Wajib surat dokter).',
                'is_active' => true,
                'default_days' => null, // Tidak terbatas selama ada bukti medis
                'gender' => 'all',
                'requires_family_status' => false,
            ],
            [
                'name' => 'Cuti Hamil & Melahirkan',
                // 'description' => 'Cuti khusus untuk persalinan (1.5 bulan sebelum & 1.5 bulan sesudah).',
                'is_active' => true,
                'default_days' => 90,
                'gender' => 'female',
                'requires_family_status' => false,
            ],
            [
                'name' => 'Cuti Haid',
                // 'description' => 'Cuti khusus wanita di hari pertama/kedua masa haid.',
                'is_active' => true,
                'default_days' => 2,
                'gender' => 'female',
                'requires_family_status' => false,
            ],
            [
                'name' => 'Cuti Menikah',
                // 'description' => 'Cuti khusus untuk melangsungkan pernikahan karyawan.',
                'is_active' => true,
                'default_days' => 3,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
            [
                'name' => 'Cuti Khitan/Baptis Anak',
                // 'description' => 'Cuti khusus untuk keperluan acara keagamaan anak.',
                'is_active' => true,
                'default_days' => 2,
                'gender' => 'all',
                'requires_family_status' => true,
            ],
            [
                'name' => 'Cuti Duka (Keluarga Inti)',
                // 'description' => 'Cuti karena adanya anggota keluarga inti yang meninggal dunia.',
                'is_active' => true,
                'default_days' => 2,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
            [
                'name' => 'Unpaid Leave (Cuti Diluar Tanggungan)',
                // 'description' => 'Cuti tanpa dibayar oleh perusahaan.',
                'is_active' => true,
                'default_days' => null,
                'gender' => 'all',
                'requires_family_status' => false,
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::create(array_merge($type, [
                'uuid' => Str::uuid(),
                'created_by_id' => $creatorId,
            ]));
        }
    }
}
