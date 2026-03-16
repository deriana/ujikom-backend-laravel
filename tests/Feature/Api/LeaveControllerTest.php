<?php

namespace Tests\Feature\Api;

use App\Enums\ApprovalStatus;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $employeeUser;

    protected User $managerUser;

    protected Employee $employeeProfile;

    protected Employee $managerProfile;

    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('manager', 'api');
        Role::findOrCreate('hr', 'api');

        // Create necessary permissions
        Permission::findOrCreate('leave.index', 'api');
        Permission::findOrCreate('leave.show', 'api');
        Permission::findOrCreate('leave.create', 'api');
        Permission::findOrCreate('leave.edit', 'api');
        Permission::findOrCreate('leave.destroy', 'api');
        Permission::findOrCreate('leave.approve', 'api');
        Permission::findOrCreate('leave.export', 'api');

        // Create an admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        Employee::factory()->create(['user_id' => $this->adminUser->id]);

        // Create a manager user
        $this->managerUser = User::factory()->create();
        $this->managerUser->assignRole('manager');
        $this->managerUser->givePermissionTo(['leave.index', 'leave.show', 'leave.create', 'leave.edit', 'leave.destroy', 'leave.approve']);
        $this->managerProfile = Employee::factory()->create(['user_id' => $this->managerUser->id]);

        // Create a regular employee user, managed by the manager
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');
        $this->employeeUser->givePermissionTo(['leave.index', 'leave.show', 'leave.create', 'leave.edit', 'leave.destroy']);

        // Ensure employee has a gender that matches the leave type
        $this->employeeProfile = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'manager_id' => $this->managerProfile->id,
            'gender' => 'female',
        ]);

        // Create a leave type
        $this->leaveType = LeaveType::factory()->create([
            'name' => 'Annual Leave',
            'gender' => 'all',
        ]);

        // Clear permission cache
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function employee_can_list_their_own_leaves()
    {
        Leave::factory()->count(2)->create(['employee_id' => $this->employeeProfile->id]);
        Leave::factory()->count(3)->create(); // Other leaves

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->getJson('/api/leaves/my-leaves');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    /** @test */
    public function admin_can_list_all_leaves()
    {
        // 1. Buat beberapa employee terlebih dahulu
        $employees = Employee::factory()->count(5)->create();

        // 2. Buat cuti untuk masing-masing employee tersebut
        foreach ($employees as $employee) {
            Leave::factory()->create([
                'employee_id' => $employee->id,
                'date_start' => now(),
                'approval_status' => ApprovalStatus::PENDING->value, // Pastikan statusnya tidak 'cancelled' atau 'rejected' jika ada filter
            ]);
        }

        // 3. Login sebagai Admin
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson('/api/leaves');

        // Jika masih 0, periksa file LeaveService.php di bagian index()
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function employee_can_create_a_leave_request()
    {
        Sanctum::actingAs($this->employeeUser, ['*']);
        Storage::fake('local');

        // Mock WorkdayService to ensure the requested dates are considered workdays
        $this->mock(\App\Services\WorkdayService::class, function ($mock) {
            $mock->shouldReceive('isWorkday')->andReturn(true);
        });

        // Pastikan saldo cuti tersedia untuk employee ini, tipe cuti ini, dan tahun ini
        \App\Models\EmployeeLeaveBalance::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => now()->year,
            'total_days' => 12,
            'used_days' => 0,
        ]);

        $payload = [
            'leave_type_uuid' => $this->leaveType->uuid,
            'date_start' => now()->addDays(5)->toDateString(),
            'date_end' => now()->addDays(6)->toDateString(),
            'reason' => 'Personal vacation',
            'attachment' => UploadedFile::fake()->image('document.jpg'),
        ];

        $response = $this->postJson('/api/leaves', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.reason', 'Personal vacation');

        $this->assertDatabaseHas('leaves', [
            'employee_id' => $this->employeeProfile->id,
            'reason' => 'Personal vacation',
        ]);

        $leave = Leave::first();
        $this->assertDatabaseHas('leave_approvals', [
            'leave_id' => $leave->id,
            'approver_id' => $this->managerProfile->id,
        ]);
    }

    /** @test */
    public function employee_can_view_their_own_leave_request()
    {
        $leave = Leave::factory()->create(['employee_id' => $this->employeeProfile->id]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->getJson("/api/leaves/{$leave->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $leave->uuid);
    }

    /** @test */
    public function manager_can_list_requests_for_approval()
    {
        $leave = Leave::factory()->create(['employee_id' => $this->employeeProfile->id]);
        LeaveApproval::factory()->create([
            'leave_id' => $leave->id,
            'approver_id' => $this->managerProfile->id,
            'status' => ApprovalStatus::PENDING,
        ]);

        Leave::factory()->create();

        Sanctum::actingAs($this->managerUser, ['*']);

        $response = $this->getJson('/api/approvals/leaves');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $leave->uuid);
    }

    /** @test */
    public function manager_can_approve_a_leave_request()
    {
        $leave = Leave::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'approval_status' => ApprovalStatus::PENDING,
            'leave_type_id' => $this->leaveType->id,
            // Ensure dates are in the future to pass WorkdayService checks if any
            'date_start' => now()->addMonth()->startOfMonth(),
            'date_end' => now()->addMonth()->startOfMonth()->addDays(3),
        ]);

        // Create balance to avoid failure during finalizeLeave
        \App\Models\EmployeeLeaveBalance::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'leave_type_id' => $leave->leave_type_id,
            'year' => $leave->date_start->year,
            'total_days' => 12,
            'used_days' => 0,
        ]);

        $approval = LeaveApproval::factory()->create(['leave_id' => $leave->id, 'approver_id' => $this->managerProfile->id, 'status' => ApprovalStatus::PENDING]);

        Sanctum::actingAs($this->managerUser, ['*']);

        $payload = ['approve' => true, 'note' => 'Approved. Have a good time.'];

        $response = $this->putJson("/api/leaves/approvals/{$approval->uuid}/approve", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leave_approvals', ['uuid' => $approval->uuid, 'status' => ApprovalStatus::APPROVED->value]);
        $this->assertDatabaseHas('leaves', ['uuid' => $leave->uuid, 'approval_status' => ApprovalStatus::APPROVED->value]);
    }

    /** @test */
    public function manager_can_reject_a_leave_request()
    {
        $leave = Leave::factory()->create(['employee_id' => $this->employeeProfile->id, 'approval_status' => ApprovalStatus::PENDING]);
        $approval = LeaveApproval::factory()->create(['leave_id' => $leave->id, 'approver_id' => $this->managerProfile->id, 'status' => ApprovalStatus::PENDING]);

        Sanctum::actingAs($this->managerUser, ['*']);

        $payload = ['approve' => false, 'note' => 'Rejected due to project deadline.'];

        $response = $this->putJson("/api/leaves/approvals/{$approval->uuid}/approve", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leave_approvals', ['uuid' => $approval->uuid, 'status' => ApprovalStatus::REJECTED->value]);
        $this->assertDatabaseHas('leaves', ['uuid' => $leave->uuid, 'approval_status' => ApprovalStatus::REJECTED->value]);
    }

    /** @test */
    public function employee_can_cancel_their_pending_leave_request()
    {
        $leave = Leave::factory()->create(['employee_id' => $this->employeeProfile->id, 'approval_status' => ApprovalStatus::PENDING]);

        Sanctum::actingAs($this->employeeUser, ['*']);
        $this->employeeUser->givePermissionTo(['leave.destroy', 'leave.index']);
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->deleteJson("/api/leaves/{$leave->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('leaves', ['id' => $leave->id]);
    }

    /** @test */
    public function user_can_download_leave_attachment()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('medical_cert.pdf');
        $path = $file->store('private/leave_attachments');
        $filename = basename($path);

        Leave::factory()->create(['employee_id' => $this->employeeProfile->id, 'attachment' => $path]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->get("/api/leaves/download-attachment/{$filename}");

        $response->assertStatus(200)
            ->assertHeader('content-disposition', 'attachment; filename='.$filename);
    }

    /** @test */
    public function employee_cannot_approve_a_leave_request()
    {
        $leave = Leave::factory()->create(['employee_id' => $this->employeeProfile->id, 'approval_status' => ApprovalStatus::PENDING]);
        $approval = LeaveApproval::factory()->create(['leave_id' => $leave->id, 'approver_id' => $this->managerProfile->id, 'status' => ApprovalStatus::PENDING]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $payload = ['approve' => true];

        $response = $this->putJson("/api/leaves/approvals/{$approval->uuid}/approve", $payload);

        $response->assertStatus(403);
    }
}
