<?php

namespace Tests\Feature\Api;

use App\Models\Allowance;
use App\Models\User;
use Spatie\Permission\Models\Role; // Gunakan model Spatie langsung
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::findOrCreate('admin', 'api');
        $staffRole = Role::findOrCreate('employee', 'api');

        $this->admin = User::factory()->create();
        $this->staff = User::factory()->create();

        $this->admin->assignRole($adminRole);
        $this->staff->assignRole($staffRole);

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_list_all_allowances_for_authorized_user()
    {
        Allowance::factory()->count(3)->create();

        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/allowances');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_prevents_unauthorized_user_from_creating_allowance()
    {
        $payload = [
            'name' => 'Tunjangan Makan',
            'amount' => 50000,
            'type' => 'fixed', // Pastikan type ada jika required
        ];

        Sanctum::actingAs($this->staff, ['*']);
        $response = $this->postJson('/api/allowances', $payload);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_create_allowance_with_valid_data()
    {
        $payload = [
            'name' => 'Tunjangan Transport',
            'amount' => 150000,
            'type' => 'fixed',
        ];

        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->postJson('/api/allowances', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('allowances', ['name' => 'Tunjangan Transport']);
    }

    /** @test */
    public function it_can_show_specific_allowance_detail()
    {
        $allowance = Allowance::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson("/api/allowances/{$allowance->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', $allowance->name);
    }

    /** @test */
    public function it_can_soft_delete_allowance()
    {
        $allowance = Allowance::factory()->create();

        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->deleteJson("/api/allowances/{$allowance->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('allowances', ['id' => $allowance->id]);
    }

    /** @test */
    public function it_can_list_trashed_allowances()
    {
        $allowance = Allowance::factory()->create();
        $allowance->delete();

        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/allowances/trashed');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_restore_trashed_allowance()
    {
        $allowance = Allowance::factory()->create();
        $allowance->delete();

        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->postJson("/api/allowances/restore/{$allowance->uuid}");

        $response->assertStatus(200);
        $this->assertNotSoftDeleted('allowances', ['id' => $allowance->id]);
    }

    /** @test */
    public function it_can_force_delete_allowance()
    {
        $allowance = Allowance::factory()->create();
        $allowance->delete();

        Sanctum::actingAs($this->admin, ['*']);
        $response = $this->deleteJson("/api/allowances/force-delete/{$allowance->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('allowances', ['id' => $allowance->id]);
    }
}
