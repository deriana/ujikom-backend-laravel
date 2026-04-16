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

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');

        // 2. Create Admin User (Tanpa profil Employee untuk simulasi case real)
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // 3. Create Target Employee (Yang akan dinilai)
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');
        Employee::factory()->create(['user_id' => $this->employeeUser->id]);

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_assessments_as_admin()
    {
        Assessment::factory()->count(3)->create(['period' => '2025-01-01']);

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/assessments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_a_new_assessment_even_if_admin_has_no_employee_profile()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Employee yang dinilai
        $evaluatee = Employee::factory()->create();

        $payload = [
            'evaluatee_nik' => $evaluatee->nik,
            'period'        => '2025-04', // Kirim YYYY-MM, biarkan Service yang urus -01
            'note'          => 'Performance is stable.',
            'assessment_details' => [
                [
                    'assessment_category_uuid' => \App\Models\AssessmentCategory::factory()->create()->uuid,
                    'score' => 85,
                ]
            ]
        ];

        $response = $this->postJson('/api/assessments', $payload);

        $response->assertStatus(201);

        // Pastikan tersimpan dengan evaluator_id NULL (karena admin tidak punya profil employee)
        $this->assertDatabaseHas('assessments', [
            'evaluator_id' => null,
            'evaluatee_id' => $evaluatee->id,
            'period'       => '2025-04-01',
            'note'         => 'Performance is stable.',
        ]);
    }

    /** @test */
    public function it_can_show_specific_assessment_details()
    {
        $assessment = Assessment::factory()->create(['period' => '2025-01-01']);

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson("/api/assessments/{$assessment->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $assessment->uuid);
    }

    /** @test */
    public function it_can_update_assessment_note_and_period()
    {
        $assessment = Assessment::factory()->create(['period' => '2025-01-01']);

        Sanctum::actingAs($this->admin, ['*']);

        $newData = [
            'note'   => 'Updated feedback note',
            'period' => '2026-12-01' // Menguji apakah update period bekerja
        ];

        $response = $this->putJson("/api/assessments/{$assessment->uuid}", $newData);

        $response->assertStatus(200);

        // Verifikasi database untuk memastikan period BERUBAH
        $this->assertDatabaseHas('assessments', [
            'uuid'   => $assessment->uuid,
            'note'   => 'Updated feedback note',
            'period' => '2026-12-01'
        ]);
    }

    /** @test */
    public function it_can_delete_an_assessment()
    {
        $assessment = Assessment::factory()->create(['period' => '2025-01-01']);

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->deleteJson("/api/assessments/{$assessment->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);
    }
}
