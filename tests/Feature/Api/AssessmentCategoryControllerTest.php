<?php

namespace Tests\Feature\Api;

use App\Models\AssessmentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AssessmentCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Role & Admin
        Role::findOrCreate('admin', 'api');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        Sanctum::actingAs($this->admin, ['*']);

        // Lupakan cache permission agar tidak ada konflik role saat testing
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    #[Test]
    public function it_can_list_all_assessment_categories()
    {
        AssessmentCategory::factory()->count(3)->create();

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/assessment_category');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'message', 'data'])
                 ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_create_an_assessment_category()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $payload = [
            'name' => 'Technical Skills',
            'description' => 'Penilaian kemampuan teknis pemrograman',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/assessment_category', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('assessment_categories', [
            'name' => 'Technical Skills'
        ]);
    }

    #[Test]
    public function it_can_update_an_assessment_category()
    {
        $category = AssessmentCategory::factory()->create(['name' => 'Old Name']);

        Sanctum::actingAs($this->admin, ['*']);

        $payload = [
            'name' => 'New Name Updated',
            'is_active' => false
        ];

        $response = $this->putJson("/api/assessment_category/{$category->uuid}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('assessment_categories', [
            'uuid' => $category->uuid,
            'name' => 'New Name Updated'
        ]);
    }

    #[Test]
    public function it_can_delete_an_assessment_category()
    {
        $category = AssessmentCategory::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->deleteJson("/api/assessment_category/{$category->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('assessment_categories', ['id' => $category->id]);
    }

    #[Test]
    public function it_can_toggle_assessment_category_status()
    {
        $category = AssessmentCategory::factory()->create(['is_active' => true]);

        Sanctum::actingAs($this->admin, ['*']);

        // Pastikan endpoint ini sudah terdaftar di routes/api.php
        $response = $this->putJson("/api/assessment_category/{$category->uuid}/toggle-status");

        $response->assertStatus(200);

        // Cek apakah statusnya terbalik (true jadi false)
        $this->assertDatabaseHas('assessment_categories', [
            'uuid' => $category->uuid,
            'is_active' => false
        ]);
    }

    #[Test]
    public function unauthorized_user_cannot_create_category()
    {
        $regularUser = User::factory()->create();
        // User ini tidak diberi role admin

        Sanctum::actingAs($regularUser, ['*']);

        $response = $this->postJson('/api/assessment_category', [
            'name' => 'Forbidden',
            'description' => 'Some description',
            'is_active' => true,
        ]);

        // Pastikan statusnya 403 Forbidden karena Policy
        $response->assertStatus(403);
    }
}
