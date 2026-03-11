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

/**
 * Class UserController
 *
 * Controller untuk mengelola data pengguna (User) dan profil karyawan,
 * termasuk manajemen password, biometrik, dan saldo cuti.
 */
class UserController extends Controller
{
    protected $userService; /**< Instance dari UserService untuk logika bisnis pengguna */

    /**
     * Membuat instance UserController baru.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Menampilkan daftar semua pengguna.
     *
     * @return \Illuminate\Http\JsonResponse
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
     * Menyimpan data pengguna baru ke database.
     *
     * @param CreateUserRequest $request
     * @return \Illuminate\Http\JsonResponse
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
     * Menampilkan detail data pengguna tertentu.
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
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
     * Memperbarui data pengguna yang sudah ada.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
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
     * Menghapus data pengguna (Soft Delete).
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('destroy', $user);

        $this->userService->delete($user);

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Memulihkan data pengguna yang telah dihapus (Restore).
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
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

    /**
     * Menghapus data pengguna secara permanen dari database.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(string $uuid): JsonResponse
    {
        $this->authorize('forceDelete', User::class);

        $this->userService->forceDelete($uuid);

        return $this->successResponse(null, 'User permanently deleted');
    }

    /**
     * Menghentikan masa kerja karyawan (Resign atau PHK).
     *
     * @param Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Mengubah password pengguna oleh Administrator.
     *
     * @param Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
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
            null,
            'Password Changed successfully'
        );
    }

    /**
     * Mengubah status aktif/non-aktif akun pengguna.
     *
     * @param Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Mengambil daftar pengguna yang berada di dalam trash (terhapus sementara).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTrashed(): JsonResponse
    {
        $this->authorize('restore', User::class);

        $users = $this->userService->getTrashed();

        return $this->successResponse(
            UserResource::collection($users),
            'Trashed Users fetched successfully'
        );
    }

    /**
     * Mengunggah foto profil untuk pengguna tertentu.
     *
     * @param Request $request
     * @param User $user
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePhoto(Request $request, User $user, string $uuid): JsonResponse
    {
        // $this->authorize('edit', $user);

        $photoFile = $request->file('profile_photo');

        $user = $this->userService->uploadProfilePhoto($user, $photoFile, $uuid);

        return $this->successResponse(
            'Profile photo uploaded successfully'
        );
    }

    /**
     * Mengambil daftar pengguna yang memiliki peran sebagai Manager.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getManagers(): JsonResponse
    {
        $users = $this->userService->getManagers();

        return $this->successResponse(
            ManagerResource::collection($users),
            'Managers fetched successfully'
        );
    }

    /**
     * Mengambil daftar ringkas (lite) dari semua karyawan.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeesLite(): JsonResponse
    {
        $users = $this->userService->getEmployeesLite();

        return $this->successResponse(
            EmployeeLiteResources::collection($users),
            'Employees fetched successfully'
        );
    }

    /**
     * Mengambil data profil pengguna yang sedang login.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        $user = Auth::user();

        $employee = $this->userService->show($user);

        return $this->successResponse(
            new UserResource($employee),
            'Profile fetched successfully'
        );
    }

    /**
     * Mengubah password akun milik pengguna yang sedang login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Memperbarui data deskriptor wajah (biometrik) untuk absensi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Mengambil daftar saldo cuti seluruh karyawan.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeeLeaveBalances(): JsonResponse
    {
        $leave_balances = $this->userService->getEmployeeLeaveBalances();

        return $this->successResponse(
            EmployeeLeaveBalanceResource::collection($leave_balances),
            'LeaveBalances fetched successfully'
        );
    }

    /**
     * Mengambil data saldo cuti milik pengguna yang sedang login.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyLeaveBalances(): JsonResponse
    {
        $leave_balances = $this->userService->getMyLeaveBalances();

        return $this->successResponse(
            new EmployeeLeaveBalanceResource($leave_balances),
            'My leave balances fetched successfully'
        );
    }
}
