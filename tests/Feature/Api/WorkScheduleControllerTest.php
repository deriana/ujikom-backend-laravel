<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WorkMode;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin', 'api');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function admin_can_list_work_schedules()
    {
        WorkSchedule::factory()->count(3)->create();

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/work_schedules');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    /** @test */
  /** @test */
    public function admin_can_create_work_schedule()
    {
        Sanctum::actingAs($this->admin);

        // Pastikan WorkMode tersedia
        $workMode = \App\Models\WorkMode::factory()->create();

        $scheduleName = 'General Office Schedule ' . uniqid(); // Pastikan UNIK

        $payload = [
            'name' => $scheduleName,
            'description' => 'Default schedule for all office staff',
            'is_active' => true,
            'work_mode_id' => $workMode->id,
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
            'break_start_time' => '12:00',
            'break_end_time' => '13:00',
            'late_tolerance_minutes' => 15,
            'requires_office_location' => true,
        ];

        $response = $this->postJson('/api/work_schedules', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('work_schedules', [
            'name' => $scheduleName,
            'work_mode_id' => $workMode->id,
            'work_start_time' => '08:00',
        ]);
    }

    /** @test */
    public function admin_can_soft_delete_work_schedule()
    {
        $schedule = WorkSchedule::factory()->create();

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/work_schedules/{$schedule->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('work_schedules', ['id' => $schedule->id]);
    }

    /** @test */
    public function admin_can_restore_work_schedule()
    {
        $schedule = WorkSchedule::factory()->create(['deleted_at' => now()]);

        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/work_schedules/restore/{$schedule->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('work_schedules', [
            'id' => $schedule->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function admin_can_list_trashed_work_schedules()
    {
        $schedule = WorkSchedule::factory()->create();
        $schedule->delete();

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/work_schedules/trashed');

        $response->assertStatus(200)
            ->assertJsonFragment(['uuid' => $schedule->uuid]);
    }

    /** @test */
    public function admin_can_force_delete_work_schedule()
    {
        $schedule = WorkSchedule::factory()->create();
        $schedule->delete();

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/work_schedules/force-delete/{$schedule->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('work_schedules', ['id' => $schedule->id]);
    }
}
