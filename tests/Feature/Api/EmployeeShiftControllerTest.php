<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\WorkdayService;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeShiftControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Employee $employee;
    protected ShiftTemplate $shiftTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('hr', 'api');

        // Create Admin User
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // Create Employee
        $user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $user->id]);

        // Create Shift Template
        $this->shiftTemplate = ShiftTemplate::factory()->create();

        // Mock WorkdayService to bypass holiday/weekend validation which uses DATE_FORMAT (incompatible with SQLite)
        $this->mock(WorkdayService::class, function ($mock) {
            $mock->shouldReceive('isWorkday')->andReturn(true);
        });

        // Clear permission cache
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_employee_shifts()
    {
        EmployeeShift::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $this->shiftTemplate->id,
            'created_by_id' => $this->adminUser->id
        ]);

        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson('/api/employee_shift');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function it_can_create_employee_shift()
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'employee_nik' => $this->employee->nik,
            'shift_template_uuid' => $this->shiftTemplate->uuid,
            'shift_date' => now()->addDay()->toDateString(),
        ];

        $response = $this->postJson('/api/employee_shift', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $this->employee->id,
            'shift_template_id' => $this->shiftTemplate->id,
            'shift_date' => $payload['shift_date'] . ' 00:00:00',
        ]);
    }

    /** @test */
    public function it_can_show_employee_shift()
    {
        $shift = EmployeeShift::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $this->shiftTemplate->id,
            'created_by_id' => $this->adminUser->id
        ]);

        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson("/api/employee_shift/{$shift->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $shift->uuid);
    }

    /** @test */
    public function it_can_update_employee_shift()
    {
        $shift = EmployeeShift::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $this->shiftTemplate->id,
            'created_by_id' => $this->adminUser->id
        ]);

        $newTemplate = ShiftTemplate::factory()->create();

        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'employee_nik' => $this->employee->nik,
            'shift_template_uuid' => $newTemplate->uuid,
            'shift_date' => now()->addDays(2)->toDateString(),
        ];

        $response = $this->putJson("/api/employee_shift/{$shift->uuid}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('employee_shifts', [
            'id' => $shift->id,
            'shift_template_id' => $newTemplate->id,
            'shift_date' => $payload['shift_date'] . ' 00:00:00',
        ]);
    }

    /** @test */
    public function it_can_delete_employee_shift()
    {
        $shift = EmployeeShift::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $this->shiftTemplate->id,
            'created_by_id' => $this->adminUser->id
        ]);

        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->deleteJson("/api/employee_shift/{$shift->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('employee_shifts', ['id' => $shift->id]);
    }
}
