<?php

namespace Tests\Feature\Api;

use App\Models\Assessment;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employeeUser;
    protected User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Roles (Ensure guard is 'api')
        $adminRole = Role::findOrCreate('admin', 'api');
        $employeeRole = Role::findOrCreate('employee', 'api');
        $hrRole = Role::findOrCreate('hr', 'api');

        // 2. Create Users
        $this->admin = User::factory()->create();
        $this->employeeUser = User::factory()->create();
        $this->hr = User::factory()->create();

        // 3. Assign Roles
        $this->admin->assignRole($adminRole);
        $this->employeeUser->assignRole($employeeRole);
        $this->hr->assignRole($hrRole);

        // 4. Clear Spatie permission cache
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_assessments_as_admin()
    {
        Assessment::factory()->count(3)->create();

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/assessments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_a_new_assessment()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Kita buat data dummy tapi tidak disimpan ke DB (make)
        $assessment = Assessment::factory()->make();

        // Sesuaikan payload dengan key yang diharapkan Controller (menggunakan NIK)
        $payload = [
            'evaluator_nik' => Employee::find($assessment->evaluator_id)->nik,
            'evaluatee_nik' => Employee::find($assessment->evaluatee_id)->nik,
            'period'        => $assessment->period,
            'note'          => $assessment->note,
        ];

        $response = $this->postJson('/api/assessments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.period', $payload['period']);

        $this->assertDatabaseHas('assessments', [
            'period' => $payload['period'] . '-01',
            'note'   => $payload['note'],
        ]);
    }

    /** @test */
    public function it_can_show_specific_assessment_details()
    {
        $assessment = Assessment::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);

        // MENGGUNAKAN uuid karena getRouteKeyName() di model adalah uuid
        $response = $this->getJson("/api/assessments/{$assessment->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $assessment->uuid);
    }

    /** @test */
    public function it_can_update_an_existing_assessment()
    {
        $assessment = Assessment::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);

        $newData = [
            'note' => 'Updated feedback note',
            'period' => '2026-12'
        ];

        // MENGGUNAKAN uuid
        $response = $this->putJson("/api/assessments/{$assessment->uuid}", $newData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('assessments', [
            'uuid' => $assessment->uuid,
            'note' => 'Updated feedback note'
        ]);
    }

    /** @test */
    public function it_can_delete_an_assessment()
    {
        $assessment = Assessment::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);

        // MENGGUNAKAN uuid
        $response = $this->deleteJson("/api/assessments/{$assessment->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);
    }
}
