<?php

namespace Tests\Feature\Api;

use App\Enums\ApprovalStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OvertimeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $employeeUser;
    protected User $managerUser;
    protected Employee $employeeProfile;
    protected Employee $managerProfile;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('manager', 'api');
        Role::findOrCreate('hr', 'api');

        // Create necessary permissions
        Permission::findOrCreate('overtime.index', 'api');
        Permission::findOrCreate('overtime.show', 'api');
        Permission::findOrCreate('overtime.create', 'api');
        Permission::findOrCreate('overtime.edit', 'api');
        Permission::findOrCreate('overtime.destroy', 'api');
        Permission::findOrCreate('overtime.approve', 'api');

        // Create an admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        Employee::factory()->create(['user_id' => $this->adminUser->id]);

        // Create a manager user
        $this->managerUser = User::factory()->create();
        $this->managerUser->assignRole('manager');
        $this->managerUser->givePermissionTo(['overtime.approve']);
        $this->managerProfile = Employee::factory()->create(['user_id' => $this->managerUser->id]);

        // Create a regular employee user, managed by the manager
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');
        $this->employeeProfile = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'manager_id' => $this->managerProfile->id,
        ]);

        // Assign permissions to employee role
        $employeeRole = Role::findByName('employee', 'api');
        $employeeRole->givePermissionTo(['overtime.index', 'overtime.show', 'overtime.create', 'overtime.edit', 'overtime.destroy']);
        // Create an attendance record for the employee
        $this->attendance = Attendance::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'date' => now()->toDateString(),
            'clock_out' => null,
        ]);

        // Clear permission cache
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function employee_can_list_their_own_overtimes()
    {
        Overtime::factory()->count(2)->create(['employee_id' => $this->employeeProfile->id]);
        Overtime::factory()->count(3)->create(); // Other overtimes

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->getJson('/api/overtime');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function manager_can_list_overtimes_for_approval()
    {
        Overtime::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'status' => ApprovalStatus::PENDING,
        ]);
        Overtime::factory()->create(['status' => ApprovalStatus::APPROVED]);

        Sanctum::actingAs($this->managerUser, ['*']);

        $response = $this->getJson('/api/approvals/overtime');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function employee_can_create_an_overtime_request()
    {
        // Ensure attendance has no clock_out to pass validation
        $this->attendance->update([
            'clock_in' => '08:00:00',
            'clock_out' => null,
        ]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $payload = [
            'attendance_uuid' => $this->attendance->uuid,
            'reason' => 'Finishing up the quarterly report.',
        ];

        $response = $this->postJson('/api/overtime', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.reason', 'Finishing up the quarterly report.');

        $this->assertDatabaseHas('overtimes', [
            'employee_id' => $this->employeeProfile->id,
            'attendance_id' => $this->attendance->id,
            'reason' => 'Finishing up the quarterly report.',
            'duration_minutes' => 0,
        ]);
    }

    /** @test */
    public function employee_can_show_their_own_overtime_request()
    {
        $overtime = Overtime::factory()->create(['employee_id' => $this->employeeProfile->id]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->getJson("/api/overtime/{$overtime->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $overtime->uuid);
    }

    /** @test */
    public function employee_can_update_their_pending_overtime_request()
    {
        $overtime = Overtime::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'status' => ApprovalStatus::PENDING,
        ]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $payload = [
            'reason' => 'Updated reason for overtime.',
            'duration_minutes' => 90,
        ];

        $response = $this->putJson("/api/overtime/{$overtime->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.reason', 'Updated reason for overtime.');

        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'reason' => 'Updated reason for overtime.',
        ]);
    }

    /** @test */
    public function employee_can_cancel_their_pending_overtime_request()
    {
        $overtime = Overtime::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'status' => ApprovalStatus::PENDING,
        ]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->deleteJson("/api/overtime/{$overtime->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('overtimes', ['id' => $overtime->id]);
    }

    /** @test */
    public function manager_can_approve_an_overtime_request()
    {
        $overtime = Overtime::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'status' => ApprovalStatus::PENDING,
        ]);

        Sanctum::actingAs($this->managerUser, ['*']);

        $payload = ['approve' => true, 'note' => 'Approved. Good work.'];

        $response = $this->putJson("/api/overtime/{$overtime->uuid}/approve", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'status' => ApprovalStatus::APPROVED->value,
        ]);
    }

    /** @test */
    public function manager_can_reject_an_overtime_request()
    {
        $overtime = Overtime::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'status' => ApprovalStatus::PENDING,
        ]);

        Sanctum::actingAs($this->managerUser, ['*']);

        $payload = ['approve' => false, 'note' => 'Not necessary for today.'];

        $response = $this->putJson("/api/overtime/{$overtime->uuid}/approve", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'status' => ApprovalStatus::REJECTED->value,
        ]);
    }

    /** @test */
    public function employee_cannot_request_overtime_after_clocking_out()
    {
        // Gak usah pake factory create, update aja yang udah ada di setUp()
        $this->attendance->update([
            'clock_out' => '17:00:00',
        ]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $payload = [
            'attendance_uuid' => $this->attendance->uuid,
            'reason' => 'Trying to request after clock out.',
        ];

        $response = $this->postJson('/api/overtime', $payload);

        // Karena Service kamu throw Exception, Laravel balikin 500
        $response->assertStatus(500);
    }
}
