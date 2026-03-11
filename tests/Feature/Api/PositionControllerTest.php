<?php

namespace Tests\Feature\Api;

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Role Admin
        Role::findOrCreate('admin', 'api');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function admin_can_list_all_positions()
    {
        Position::factory()->count(3)->create();

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/positions');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_create_position()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'name' => 'Senior Developer',
            'base_salary' => 12000000,
            'description' => 'Lead technical projects',
        ];

        $response = $this->postJson('/api/positions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('positions', ['name' => 'Senior Developer']);
    }

    /** @test */
    public function admin_can_soft_delete_position()
    {
        $position = Position::factory()->create();

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/positions/{$position->uuid}");

        $response->assertStatus(200);
        // Cek apakah data masuk ke trash (Soft Delete)
        $this->assertSoftDeleted('positions', ['id' => $position->id]);
    }

    /** @test */
    public function admin_can_restore_deleted_position()
    {
        $position = Position::factory()->create(['deleted_at' => now()]);

        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/positions/restore/{$position->uuid}");

        $response->assertStatus(200);
        $this->assertNotSoftDeleted('positions', ['id' => $position->id]);
    }

    /** @test */
    public function admin_can_list_trashed_positions()
    {
        $position = Position::factory()->create();
        $position->delete();

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/positions/trashed');

        $response->assertStatus(200)
                 ->assertJsonFragment(['uuid' => $position->uuid]);
    }

    /** @test */
    public function admin_can_force_delete_position()
    {
        $position = Position::factory()->create();
        $position->delete();

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/positions/force-delete/{$position->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }
}
