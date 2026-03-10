<?php

namespace App\Http\Controllers\Api;

// use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\EmployeeLeaveBalanceResource;
use App\Http\Resources\EmployeeLiteResources;
use App\Http\Resources\ManagerResource;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->index();

        return $this->successResponse(
            UserResource::collection($users),
            'Users fetched successfully',
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->store($request->validated(), Auth::id());

        return $this->successResponse(
            new UserResource($user),
            'User created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user = $this->userService->show($user);

        return $this->successResponse(
            new UserDetailResource($user),
            'User fetched successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('edit', $user);

        $updated = $this->userService->update($user, $request->validated(), Auth::id());

        return $this->successResponse(
            new UserResource($updated),
            'User updated successfully'
        );
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('destroy', $user);

        $this->userService->delete($user);

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function restore(string $uuid): JsonResponse
    {
        $this->authorize('restore', User::class);

        $user = $this->userService->restore($uuid);

        return $this->successResponse(
            new UserResource($user),
            'User restored successfully'
        );
    }

    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', User::class);

        $this->userService->forceDelete($uuid);

        return $this->successResponse(null, 'User permanently deleted');
    }

    public function terminateEmployment(Request $request, string $uuid): JsonResponse
    {
        $this->authorize('edit', User::class);

        $validated = $request->validate([
            'type' => 'required|in:resigned,terminated',
            'date' => 'nullable|date',
        ]);

        $user = $this->userService->terminateEmployment(
            $uuid,
            $validated['type'],
            $validated['date'] ?? null,
            Auth::id()
        );

        return $this->successResponse(
            new UserResource($user),
            'Terminated successfully'
        );
    }

    public function adminChangePassword(Request $request, string $uuid): JsonResponse
    {
        $this->authorize('edit', User::class);

        $validated = $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->userService->adminChangePassword(
            $uuid,
            $validated['new_password']
        );

        return $this->successResponse(
            new UserResource($user),
            'Password Changed successfully'
        );
    }

    public function status(Request $request, string $uuid): JsonResponse
    {
        $this->authorize('edit', User::class);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user = $this->userService->status($uuid, $validated['is_active'], Auth::id());

        return $this->successResponse(
            new UserResource($user),
            'Status Changed successfully'
        );
    }

    public function getTrashed(): JsonResponse
    {
        $this->authorize('restore', User::class);

        $users = $this->userService->getTrashed();

        return $this->successResponse(
            UserResource::collection($users),
            'Trashed Users fetched successfully'
        );
    }

    public function uploadProfilePhoto(Request $request, User $user, string $uuid): JsonResponse
    {
        // $this->authorize('edit', $user);

        $photoFile = $request->file('profile_photo');

        $user = $this->userService->uploadProfilePhoto($user, $photoFile, $uuid);

        return $this->successResponse(
            'Profile photo uploaded successfully'
        );
    }

    public function getManagers(): JsonResponse
    {
        $users = $this->userService->getManagers();

        return $this->successResponse(
            ManagerResource::collection($users),
            'Managers fetched successfully'
        );
    }

    public function getEmployeesLite(): JsonResponse
    {
        $users = $this->userService->getEmployeesLite();

        return $this->successResponse(
            EmployeeLiteResources::collection($users),
            'Employees fetched successfully'
        );
    }

    public function getProfile()
    {
        $user = Auth::user();

        $employee = $this->userService->show($user);

        return $this->successResponse(
            new UserResource($employee),
            'Profile fetched successfully'
        );
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        $this->userService->changePassword(
            $user,
            $validated['current_password'],
            $validated['new_password']
        );

        return $this->successResponse(
            null,
            'Password changed successfully'
        );
    }

    public function updateBiometricDescriptors(Request $request)
    {
        $request->validate([
            'descriptors' => 'required|array|size:5',
            'descriptors.*' => 'required|array|min:128',
        ]);

        $this->userService->updateBiometricDescriptors(
            Auth::user(),
            $request->descriptors
        );

        return $this->successResponse(
            null,
            'Biometric descriptors updated successfully'
        );
    }

    public function getEmployeeLeaveBalances(): JsonResponse
    {
        $leave_balances = $this->userService->getEmployeeLeaveBalances();

        return $this->successResponse(
            EmployeeLeaveBalanceResource::collection($leave_balances),
            'LeaveBalances fetched successfully'
        );
    }

    public function getMyLeaveBalances(): JsonResponse
    {
        $leave_balances = $this->userService->getMyLeaveBalances();

        return $this->successResponse(
            new EmployeeLeaveBalanceResource($leave_balances),
            'My leave balances fetched successfully'
        );
    }
}
