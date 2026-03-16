<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\User;
use App\Models\ShiftTemplate;
use App\Services\WorkdayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Employee $adminEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');

        // 2. Setup User & Employee Profile
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Penting: Hubungkan User ke Employee agar auth()->user()->employee bekerja
        $this->adminEmployee = Employee::factory()->create(['user_id' => $this->adminUser->id]);

        // 3. Mock WorkdayService to always return true to avoid holiday/weekend validation errors
        $this->mock(WorkdayService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isWorkday')->andReturn(true);
        });

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_all_attendance_requests()
    {
        AttendanceRequest::factory()->count(3)->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson('/api/attendance_request');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function it_can_store_a_shift_change_request()
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $shift = ShiftTemplate::factory()->create();

        $payload = [
            'request_type' => 'SHIFT',
            'shift_template_uuid' => $shift->uuid,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Tukar shift karena ada urusan keluarga',
        ];

        // Pastikan URL sesuai dengan api.php Anda (jamak/tunggal)
        $response = $this->postJson('/api/attendance_request', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_requests', [
            'reason' => 'Tukar shift karena ada urusan keluarga',
            'shift_template_id' => $shift->id
        ]);
    }

    /** @test */
    public function it_can_show_attendance_request_detail_by_uuid()
    {
        $attendanceRequest = AttendanceRequest::factory()->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        // Menggunakan UUID sesuai getRouteKeyName() di model
        $response = $this->getJson("/api/attendance_request/{$attendanceRequest->uuid}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.uuid', $attendanceRequest->uuid);
    }

    /** @test */
    public function it_can_approve_attendance_request()
    {
        // Create request with fixed dates to avoid potential holiday logic conflicts in SQLite
        $attendanceRequest = AttendanceRequest::factory()->create([
            'status' => 0,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-02'
        ]);
        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'approve' => true,
            'note' => 'Disetujui, silakan bertugas.'
        ];

        $response = $this->putJson("/api/attendance_request/{$attendanceRequest->uuid}/approve", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendance_requests', [
            'uuid' => $attendanceRequest->uuid,
            'status' => 1,
            'approved_by_id' => $this->adminEmployee->id // Approver diambil dari Employee profile admin
        ]);
    }

    /** @test */
    public function it_can_delete_attendance_request()
    {
        $attendanceRequest = AttendanceRequest::factory()->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->deleteJson("/api/attendance_request/{$attendanceRequest->uuid}");

        $response->assertStatus(200);
        // Memastikan record hilang atau terhapus (soft delete jika model menggunakan SoftDeletes)
        $this->assertDatabaseMissing('attendance_requests', ['id' => $attendanceRequest->id]);
    }
}
