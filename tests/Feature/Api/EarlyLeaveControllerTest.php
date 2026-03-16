<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\EarlyLeave;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EarlyLeaveControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $employeeUser;
    protected Employee $employeeProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');

        // Setup Permissions
        Permission::findOrCreate('early-leave.index', 'api');
        Permission::findOrCreate('early-leave.show', 'api');
        Permission::findOrCreate('early-leave.create', 'api');
        Permission::findOrCreate('early-leave.edit', 'api');
        Permission::findOrCreate('early-leave.destroy', 'api');
        Permission::findOrCreate('early-leave.approve', 'api');
        Permission::findOrCreate('early-leave.export', 'api');

        // Create Admin User
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        Employee::factory()->create(['user_id' => $this->adminUser->id]);

        // Create Employee User
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');
        $this->employeeProfile = Employee::factory()->create(['user_id' => $this->employeeUser->id]);

        // Clear permission cache
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_early_leaves()
    {
        EarlyLeave::factory()->count(3)->create(['employee_id' => $this->employeeProfile->id]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->getJson('/api/early_leaves');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function it_can_create_early_leave_request()
    {
        // Create attendance record for today with a clock_in time to allow calculation
        // If minutes_early is calculated automatically by the service,
        // we need to ensure the attendance record supports the requested duration.
        Attendance::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->startOfDay()->addHours(8),
            'clock_out' => null,
        ]);

        Sanctum::actingAs($this->employeeUser, ['*']);

        $payload = [
            'reason' => 'Family emergency',
            'minutes_early' => 90,
        ];

        $response = $this->postJson('/api/early_leaves', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('early_leaves', [
            'employee_id' => $this->employeeProfile->id,
            'reason' => 'Family emergency'
        ]);
    }

    /** @test */
    public function it_can_show_early_leave_detail()
    {
        $earlyLeave = EarlyLeave::factory()->create(['employee_id' => $this->employeeProfile->id]);

        // Ensure the user has permission to view their own early leave
        // In many systems, 'view' policy allows owners to see their own records.
        // If it fails with 403, ensure the Policy or Service allows this.
        $this->employeeUser->givePermissionTo('early-leave.show');

        Sanctum::actingAs($this->employeeUser, ['*']);

        // Using UUID as per conventions seen in other tests
        $response = $this->getJson("/api/early_leaves/{$earlyLeave->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $earlyLeave->uuid);
    }

    /** @test */
    public function it_can_update_early_leave()
    {
        $earlyLeave = EarlyLeave::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'status' => 0 // pending
        ]);

        $this->employeeUser->givePermissionTo('early-leave.edit');

        Sanctum::actingAs($this->employeeUser, ['*']);

        $payload = [
            'reason' => 'Updated reason',
            'minutes_early' => 45,
        ];

        // The route uses POST for update based on api.php
        $response = $this->postJson("/api/early_leaves/{$earlyLeave->uuid}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('early_leaves', [
            'id' => $earlyLeave->id,
            'reason' => 'Updated reason'
        ]);
    }

    /** @test */
    public function it_can_delete_early_leave()
    {
        $earlyLeave = EarlyLeave::factory()->create([
            'employee_id' => $this->employeeProfile->id,
            'status' => 0 // pending
        ]);

        $this->employeeUser->givePermissionTo('early-leave.destroy');

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->deleteJson("/api/early_leaves/{$earlyLeave->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('early_leaves', ['id' => $earlyLeave->id]);
    }

    /** @test */
    public function it_can_approve_early_leave()
    {
        $earlyLeave = EarlyLeave::factory()->create(['status' => 0]);

        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'approve' => true,
            'note' => 'Approved request'
        ];

        $response = $this->putJson("/api/early_leaves/approvals/{$earlyLeave->uuid}/approve", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('early_leaves', [
            'id' => $earlyLeave->id,
            'status' => 1, // Assuming 1 is approved
        ]);
    }

    /** @test */
    public function it_can_download_attachment_if_exists()
    {
        Storage::fake('local');

        $filename = 'proof.pdf';
        $path = 'private/early_leave_attachments/' . $filename;
        Storage::put($path, 'file content');

        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->getJson("/api/early_leaves/download-attachment/{$filename}");

        $response->assertStatus(200);
        $response->assertHeader('content-type');
    }
}
