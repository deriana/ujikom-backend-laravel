<?php

namespace App\Services;

use App\Models\Module;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use DomainException;
use Spatie\Permission\Models\Permission;

/**
 * Class RoleService
 *
 * Menangani logika bisnis untuk manajemen peran (role) dan izin (permission),
 * termasuk operasi CRUD peran dan sinkronisasi izin menggunakan Spatie Permission.
 */
class RoleService
{
    /**
     * Mengambil semua peran beserta izin yang terkait.
     *
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data peran.
     */
    public function index()
    {
        // 1. Retrieve all roles with eager loaded permissions
        return Role::with('permissions')->get();
    }

    /**
     * Membuat peran baru dan menetapkan izin.
     *
     * @param array $data Data peran (name, permissions).
     * @return Role Objek peran yang berhasil dibuat.
     */
    public function store(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the role record with API guard
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'api',
                'system_reserve' => false,
            ]);

            // 2. Sync permissions if provided in the request
            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->load('permissions');
        });
    }

    /**
     * Menampilkan detail dari peran tertentu.
     *
     * @param Role $role Objek peran.
     * @return Role Objek peran dengan relasi izin yang dimuat.
     */
    public function show(Role $role)
    {
        // 1. Load permissions relationship for the role
        return $role->load('permissions');
    }

    /**
     * Memperbarui nama peran dan daftar izinnya.
     *
     * @param Role $role Objek peran yang akan diperbarui.
     * @param array $data Data pembaruan.
     * @return Role Objek peran setelah diperbarui.
     * @throws DomainException Jika peran merupakan cadangan sistem.
     */
    public function update(Role $role, array $data): Role
    {
        // 1. Prevent modification of system reserved roles
        if($role->system_reserve) {
            throw new \DomainException("This role cannot be updated.");
        }

        return DB::transaction(function () use ($role, $data) {
            // 2. Update the role name
            $role->update([
                'name' => $data['name'] ?? $role->name,
            ]);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->fresh('permissions');
        });
    }

    /**
     * Menghapus peran jika bukan cadangan sistem dan tidak memiliki pengguna.
     *
     * @param Role $role Objek peran yang akan dihapus.
     * @return bool True jika berhasil dihapus.
     * @throws DomainException Jika peran adalah cadangan sistem atau masih digunakan oleh pengguna.
     */
    public function delete(Role $role): bool
    {
        // 1. Security check for system reserved roles
        if ($role->system_reserve) {
            throw new \DomainException("This role cannot be deleted.");
        }

        // 2. Prevent deletion if the role is still assigned to users
        if ($role->users()->exists()) {
            throw new \DomainException("This role cannot be deleted because it has users.");
        }

        // 3. Perform the deletion
        return DB::transaction(fn() => $role->delete());
    }

    /**
     * Mengambil semua izin yang dikelompokkan berdasarkan modul.
     *
     * @return \Illuminate\Database\Eloquent\Collection Koleksi data modul dan izin.
     */
    public function permission()
    {
        // 1. Retrieve modules with their nested permissions
        return Module::with('permissions')->get();
    }
}
