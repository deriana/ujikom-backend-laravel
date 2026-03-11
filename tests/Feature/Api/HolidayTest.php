<?php

namespace Tests\Feature\Api;

use App\Models\Holiday;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HolidayTest extends TestCase
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

        // Login via Sanctum
        Sanctum::actingAs($this->admin, ['*']);

        // Clear Permission Cache
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_fetch_all_holidays()
    {
        Holiday::factory()->count(3)->create();

        $response = $this->getJson('/api/holidays');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'message']);
    }

    /** @test */
    public function it_can_store_a_holiday()
    {
        $data = [
            'name' => 'Hari Libur Nasional',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'is_recurring' => true,
        ];

        $response = $this->postJson('/api/holidays', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('holidays', ['name' => 'Hari Libur Nasional']);
    }

    /** @test */
    public function it_can_update_a_holiday()
    {
        $holiday = Holiday::factory()->create();

        $updateData = [
            'name' => 'Libur Lebaran Updated',
            'start_date' => $holiday->start_date->format('Y-m-d'),
            'end_date' => $holiday->end_date->format('Y-m-d'),
            'is_recurring' => false,
        ];

        $response = $this->putJson("/api/holidays/{$holiday->uuid}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('holidays', ['name' => 'Libur Lebaran Updated']);
    }

    /** @test */
    public function it_can_delete_a_holiday()
    {
        $holiday = Holiday::factory()->create();

        $response = $this->deleteJson("/api/holidays/{$holiday->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('holidays', ['uuid' => $holiday->uuid]);
    }
}
