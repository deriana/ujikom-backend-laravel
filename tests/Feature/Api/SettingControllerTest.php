<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin', 'api');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Seed default settings
        Setting::factory()->create(['key' => 'general']);
        Setting::factory()->attendance()->create();
        Setting::factory()->geoFencing()->create();
    }

    /** @test */
    public function admin_can_fetch_all_settings()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/settings/get');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'message']);
    }

    /** @test */
    public function admin_can_update_attendance_times()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'late_tolerance_minutes' => 10,
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ];

        $response = $this->postJson('/api/settings/attendance', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('settings', [
            'key' => 'attendance',
        ]);

        // Cek apakah JSON values di DB terupdate
        $setting = Setting::where('key', 'attendance')->first();
        $this->assertEquals('09:00', $setting->values['work_start_time']);
    }

    /** @test */
    public function admin_can_update_geo_fencing_radius()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'office_latitude' => -6.123456,
            'office_longitude' => 106.123456,
            'radius_meters' => 500,
        ];

        $response = $this->postJson('/api/settings/geo_fencing', $payload);

        $response->assertStatus(200);
        $setting = Setting::where('key', 'geo_fencing')->first();
        $this->assertEquals(500, $setting->values['radius_meters']);
    }
}
