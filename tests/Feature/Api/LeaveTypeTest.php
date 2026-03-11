<?php

namespace Tests\Feature\Api;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Role & Admin
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('hr', 'api');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        Sanctum::actingAs($this->admin, ['*']);

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_fetch_all_leave_types()
    {
        LeaveType::factory()->count(3)->create(['gender' => 'all']);

        $response = $this->getJson('/api/leave_types');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'message']);
    }

    /** @test */
    public function it_can_store_a_leave_type()
    {
        $data = [
            'name' => 'Cuti Menikah',
            'days_limit' => 3,
            'gender' => 'all',
            'requires_family_status' => false,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/leave_types', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('leave_types', ['name' => 'Cuti Menikah']);
    }

    /** @test */
    public function it_can_update_a_leave_type()
    {
        $leaveType = LeaveType::factory()->create(['gender' => 'all']);

        $updateData = [
            'name' => 'Cuti Tahunan Updated',
            'days_limit' => 15,
            'is_active' => false,
            'gender' => 'male',
        ];

        $response = $this->putJson("/api/leave_types/{$leaveType->uuid}", $updateData);

        // Debugging: Kita bisa melihat log info yang dikirim controller di storage/logs/laravel.log
        $response->assertStatus(200);
        $this->assertDatabaseHas('leave_types', ['name' => 'Cuti Tahunan Updated']);
    }

    /** @test */
    public function it_can_delete_a_leave_type()
    {
        $leaveType = LeaveType::factory()->create(['gender' => 'all']);

        $response = $this->deleteJson("/api/leave_types/{$leaveType->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('leave_types', ['uuid' => $leaveType->uuid]);
    }
}
