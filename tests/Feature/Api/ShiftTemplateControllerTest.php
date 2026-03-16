<?php

namespace Tests\Feature\Api;

use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShiftTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('hr', 'api');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function admin_can_list_shift_templates()
    {
        ShiftTemplate::factory()->count(3)->create();

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/shift_templates');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_create_shift_template()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'name' => 'Night Shift',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'cross_day' => true,
        ];

        $response = $this->postJson('/api/shift_templates', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('shift_templates', ['name' => 'Night Shift', 'cross_day' => true]);
    }

    /** @test */
    public function admin_can_soft_delete_shift_template()
    {
        $template = ShiftTemplate::factory()->create();

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/shift_templates/{$template->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('shift_templates', ['id' => $template->id]);
    }

    /** @test */
    public function admin_can_restore_shift_template()
    {
        $template = ShiftTemplate::factory()->create(['deleted_at' => now()]);

        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/shift_templates/restore/{$template->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('shift_templates', [
            'id' => $template->id,
            'deleted_at' => null
        ]);
    }
}
