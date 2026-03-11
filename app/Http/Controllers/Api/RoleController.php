<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

/**
 * Class RoleController
 *
 * Controller untuk mengelola peran (Role) dan izin (Permission) dalam sistem,
 * menggunakan paket Spatie Permission untuk manajemen RBAC (Role-Based Access Control).
 */
class RoleController extends Controller
{
    protected RoleService $roleService; /**< Instance dari RoleService untuk logika bisnis manajemen peran */

    /**
     * Membuat instance RoleController baru.
     *
     * @param RoleService $roleService
     */
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Menampilkan daftar semua peran yang tersedia.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        // $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->index();

        return $this->successResponse(
            RoleResource::collection($roles),
            'Roles retrieved successfully'
        );
    }

    /**
     * Menampilkan detail data peran tertentu beserta izin yang terkait.
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Role $role)
    {
       $this->authorize('view', $role);

       return $this->successResponse(
           new RoleResource($role),
           'Role retrieved successfully'
       );
    }

    /**
     * Menyimpan peran baru ke database.
     *
     * @param CreateRoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->store($request->validated());

        return $this->successResponse(new RoleResource($role), 'Role created successfully', 201);
    }

    /**
     * Memperbarui data peran yang sudah ada.
     *
     * @param UpdateRoleRequest $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $updatedRole = $this->roleService->update($role, $request->validated());

        return $this->successResponse(new RoleResource($updatedRole), 'Role updated successfully');
    }

    /**
     * Menghapus data peran dari database.
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roleService->delete($role);

        return $this->successResponse(null, 'Role deleted successfully');
    }

    /**
     * Mengambil daftar semua izin (permissions) yang tersedia dalam sistem.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function permission(): JsonResponse
    {
        // $this->authorize('viewAny', Role::class);

        $permissions = $this->roleService->permission();

        return $this->successResponse(
            $permissions,
            'Permissions retrieved successfully'
        );
    }

}
