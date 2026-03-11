<?php

namespace Tests\Feature\Api;

use App\Models\AssessmentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Buat Role (Pastikan guard_name 'api')
        $adminRole = Role::findOrCreate('admin', 'api');
        $employeeRole = Role::findOrCreate('employee', 'api');

        // 2. Buat User
        $this->admin = User::factory()->create();
        $this->employee = User::factory()->create();

        // 3. Assign Role
        $this->admin->assignRole($adminRole);
        $this->employee->assignRole($employeeRole);

        // 4. Clear cache permission Spatie
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_assessment_categories()
    {
        AssessmentCategory::factory()->count(3)->create();

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/assessment_category');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_assessment_category()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $payload = [
            'name' => 'Technical Skills',
            'description' => 'Programming related skills',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/assessment_category', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Technical Skills');

        $this->assertDatabaseHas('assessment_categories', ['name' => 'Technical Skills']);
    }

    /** @test */
    public function it_prevents_unauthorized_user_from_creating_category()
    {
        Sanctum::actingAs($this->employee, ['*']);

        $payload = [
            'name' => 'Hacker Skill',
            'description' => 'Unauthorized attempt',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/assessment_category', $payload);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_update_assessment_category()
    {
        $category = AssessmentCategory::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);

        $payload = [
            'name' => 'Updated Name',
            'is_active' => true,
        ];

        $response = $this->putJson("/api/assessment_category/{$category->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    /** @test */
    public function it_can_delete_assessment_category()
    {
        $category = AssessmentCategory::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->deleteJson("/api/assessment_category/{$category->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('assessment_categories', ['uuid' => $category->uuid]);
    }

    /** @test */
    public function it_can_toggle_status_assessment_category()
    {
        $category = AssessmentCategory::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->putJson("/api/assessment_category/{$category->uuid}/toggle-status");

        $response->assertStatus(200);
    }
}
