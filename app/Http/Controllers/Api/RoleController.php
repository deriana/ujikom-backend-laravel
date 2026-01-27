<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Exception;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->index();

        return $this->successResponse(
            RoleResource::collection($roles),
            'Roles retrieved successfully'
        );
    }

    public function store(CreateRoleRequest $request)
    {
        $this->authorize('create', Role::class);

        try {
            $role = $this->roleService->store($request->validated());

            return $this->successResponse(new RoleResource($role), 'Role created successfully', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->authorize('update', $role);

        try {
            $updatedRole = $this->roleService->update($role, $request->validated());

            return $this->successResponse(new RoleResource($updatedRole), 'Role updated successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        try {
            $this->roleService->delete($role);

            return $this->successResponse(null, 'Role deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
