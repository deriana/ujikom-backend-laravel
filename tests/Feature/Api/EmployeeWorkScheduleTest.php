<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Enums\PriorityEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeWorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles (Spatie)
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('hr', 'api');

        // Create Admin User
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Penting: Pastikan profile Employee ada jika logic Service/Policy membutuhkannya
        Employee::factory()->create([
            'user_id' => $this->admin->id
        ]);

        // Autentikasi menggunakan Sanctum
        Sanctum::actingAs($this->admin, ['*']);

        // Clear cache permission agar role terdeteksi
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_fetch_all_employee_work_schedules()
    {
        EmployeeWorkSchedule::factory()->count(3)->create();

        // Menggunakan snake_case sesuai log error sistem kamu
        $response = $this->getJson('/api/employee_work_schedules');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'message']);
    }

    /** @test */
    public function it_can_store_a_new_employee_work_schedule()
    {
        $employee = Employee::factory()->create();
        $workSchedule = WorkSchedule::factory()->create();

        $data = [
            'employee_nik' => $employee->nik,
            'work_schedule_uuid' => $workSchedule->uuid,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'priority' => PriorityEnum::LEVEL_2->value,
        ];

        $response = $this->postJson('/api/employee_work_schedules', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('employee_work_schedules', [
            'employee_id' => $employee->id,
            'priority' => PriorityEnum::LEVEL_2->value
        ]);
    }

    /** @test */
    public function it_can_show_detail_employee_work_schedule()
    {
        // Setup: Pastikan relasi creator ada karena di Controller dipanggil ->load('creator')
        $schedule = EmployeeWorkSchedule::factory()->create([
            'created_by_id' => $this->admin->id
        ]);

        $response = $this->getJson("/api/employee_work_schedules/{$schedule->uuid}");

        // Debug: Jika masih error relationship allowances, pastikan sudah dihapus di Controller
        $response->assertStatus(200)
                 ->assertJsonPath('data.uuid', $schedule->uuid);
    }

    /** @test */
    public function it_can_update_employee_work_schedule()
    {
        $schedule = EmployeeWorkSchedule::factory()->create([
            'priority' => PriorityEnum::LEVEL_1->value
        ])->load('employee', 'workSchedule');

        $updateData = [
            'priority' => PriorityEnum::LEVEL_2->value,
            'employee_nik' => $schedule->employee->nik, // Menggunakan NIK sesuai request
            'work_schedule_uuid' => $schedule->workSchedule->uuid,
            'end_date' => $schedule->end_date ? $schedule->end_date->toDateString() : null,
            'start_date' => now()->toDateString(),
        ];

        $response = $this->putJson("/api/employee_work_schedules/{$schedule->uuid}", $updateData);

        // Jika error 500/422, un-comment baris bawah untuk debug
        // $response->dump();

        $response->assertStatus(200);
        $this->assertDatabaseHas('employee_work_schedules', [
            'uuid' => $schedule->uuid,
            'priority' => PriorityEnum::LEVEL_2->value
        ]);
    }

    /** @test */
    public function it_can_delete_employee_work_schedule()
    {
        $schedule = EmployeeWorkSchedule::factory()->create();

        $response = $this->deleteJson("/api/employee_work_schedules/{$schedule->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('employee_work_schedules', [
            'uuid' => $schedule->uuid
        ]);
    }
}
