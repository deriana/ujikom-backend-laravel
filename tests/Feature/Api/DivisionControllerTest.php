<?php

namespace Tests\Feature\Api;

use App\Models\Division;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DivisionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $employeeUser;
    protected User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');

        // Create an admin user and link to an employee profile
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        Employee::factory()->create(['user_id' => $this->adminUser->id]);

        // Create a regular employee user
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');
        Employee::factory()->create(['user_id' => $this->employeeUser->id]);

        // Create an HR user
        $this->hrUser = User::factory()->create();
        $this->hrUser->assignRole('hr');
        Employee::factory()->create(['user_id' => $this->hrUser->id]);

        // Clear permission cache
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function admin_can_list_all_divisions()
    {
        Division::query()->delete();
        Division::factory()->count(3)->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson('/api/divisions');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function employee_cannot_list_all_divisions()
    {
        Division::factory()->count(3)->create();
        Sanctum::actingAs($this->employeeUser, ['*']);

        $response = $this->getJson('/api/divisions');

        $response->assertStatus(403);
    }

    /** @test */
    // public function admin_can_get_divisions_with_teams_and_employees()
    // {
    //     $division = Division::factory()->create();
    //     $team = Team::factory()->create(['division_id' => $division->id]);
    //     Employee::factory()->create(['team_id' => $team->id]);

    //     Sanctum::actingAs($this->adminUser, ['*']);

    //     $response = $this->getJson('/api/divisions/with-teams-and-employees');

    //     $response->assertStatus(200)
    //         ->assertJsonStructure([
    //             'status',
    //             'message',
    //             'data' => [
    //                 '*' => ['uuid', 'name', 'teams'],
    //             ],
    //         ]);
    // }

    /** @test */
    public function admin_can_create_a_division()
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'name' => 'New Division',
            'code' => 'DIV-001',
            'description' => 'This is a new division.',
        ];

        $response = $this->postJson('/api/divisions', $payload);

        $response->assertStatus(201)->assertJsonPath('data.name', 'New Division');
        $this->assertDatabaseHas('divisions', ['name' => 'New Division']);
    }

    /** @test */
    public function admin_can_show_a_division()
    {
        $division = Division::factory()->has(Team::factory()->count(2))->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson("/api/divisions/{$division->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $division->uuid)
            ->assertJsonCount(2, 'data.teams');
    }

    /** @test */
    public function admin_can_update_a_division()
    {
        $division = Division::factory()->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = [
            'name' => 'Updated Division Name',
            'description' => 'Updated description.',
        ];

        $response = $this->putJson("/api/divisions/{$division->uuid}", $payload);

        $response->assertStatus(200)->assertJsonPath('data.name', 'Updated Division Name');
        $this->assertDatabaseHas('divisions', ['uuid' => $division->uuid, 'name' => 'Updated Division Name']);
    }

    /** @test */
    public function admin_can_delete_a_division()
    {
        $division = Division::factory()->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->deleteJson("/api/divisions/{$division->uuid}");

        $response->assertStatus(200)->assertJsonPath('message', 'Division deleted successfully');
        $this->assertSoftDeleted('divisions', ['id' => $division->id]);
    }

    /** @test */
    public function admin_can_list_trashed_divisions()
    {
        $division = Division::factory()->create();
        $division->delete();

        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->getJson('/api/divisions/trashed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $division->uuid);
    }

    /** @test */
    public function admin_can_restore_a_division()
    {
        $division = Division::factory()->create();
        $division->delete();

        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->postJson("/api/divisions/restore/{$division->uuid}");

        $response->assertStatus(200)->assertJsonPath('data.uuid', $division->uuid);
        $this->assertNotSoftDeleted('divisions', ['id' => $division->id]);
    }

    /** @test */
    public function admin_can_force_delete_a_division()
    {
        $division = Division::factory()->create();
        Sanctum::actingAs($this->adminUser, ['*']);

        $response = $this->deleteJson("/api/divisions/force-delete/{$division->uuid}");

        $response->assertStatus(200)->assertJsonPath('message', 'Division permanently deleted');
        $this->assertDatabaseMissing('divisions', ['id' => $division->id]);
    }

    /** @test */
    public function create_division_fails_with_invalid_data()
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $payload = ['name' => '']; // Invalid: name is required

        $response = $this->postJson('/api/divisions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }
}
