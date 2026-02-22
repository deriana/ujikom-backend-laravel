<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * List all roles
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

    public function show(Role $role)
    {
       $this->authorize('view', $role);

       return $this->successResponse(
           new RoleResource($role),
           'Role retrieved successfully'
       );
    }

    /**
     * Store a new role
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->store($request->validated());

        return $this->successResponse(new RoleResource($role), 'Role created successfully', 201);
    }

    /**
     * Update a role
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $updatedRole = $this->roleService->update($role, $request->validated());

        return $this->successResponse(new RoleResource($updatedRole), 'Role updated successfully');
    }

    /**
     * Delete a role
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roleService->delete($role);

        return $this->successResponse(null, 'Role deleted successfully');
    }

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
