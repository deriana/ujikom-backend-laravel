<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceCorrectionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Employee $adminEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Role & Permissions
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('hr', 'api');

        // Create User & Profile
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Link User ke Employee (karena Controller sering butuh profil employee)
        $this->adminEmployee = Employee::factory()->create(['user_id' => $this->adminUser->id]);

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_all_corrections()
    {
        AttendanceCorrection::factory()->count(2)->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson('/api/attendance_corrections');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function it_can_store_a_correction_request()
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $attendance = \App\Models\Attendance::factory()->create();

        $payload = [
            'attendance_id' => $attendance->id,
            'clock_in_requested' => '08:15',
            'clock_out_requested' => '17:15',
            'reason' => 'Fingerprint error at lobby',
            'employee_nik' => $this->adminEmployee->nik,
        ];

        $response = $this->postJson('/api/attendance_corrections', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_corrections', [
            'reason' => 'Fingerprint error at lobby',
            'employee_id' => $this->adminEmployee->id
        ]);
    }

    /** @test */
    public function it_can_show_detail_using_uuid()
    {
        $correction = AttendanceCorrection::factory()->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        // Menggunakan UUID sesuai getRouteKeyName()
        $response = $this->getJson("/api/attendance_corrections/{$correction->uuid}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.uuid', $correction->uuid);
    }

    /** @test */
    public function it_can_approve_correction_request()
    {
        $correction = AttendanceCorrection::factory()->create(['status' => 0]);
        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'approve' => true,
            'note'    => 'Approved by Manager'
        ];

        $response = $this->putJson("/api/attendance_corrections/approvals/{$correction->uuid}/approve", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendance_corrections', [
            'uuid'   => $correction->uuid,
            'status' => 1,
            'note'   => 'Approved by Manager'
        ]);
    }

    /** @test */
    public function it_can_delete_correction_request()
    {
        $correction = AttendanceCorrection::factory()->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->deleteJson("/api/attendance_corrections/{$correction->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('attendance_corrections', ['id' => $correction->id]);
    }
}
