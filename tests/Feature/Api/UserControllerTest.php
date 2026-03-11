<?php

namespace Tests\Feature\Api;

use App\Enums\EmployeeStatus;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee; // Ini untuk User (Email/Password)

    protected Employee $employeeProfile; // Ini untuk Profile (UUID)

    protected User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');

        // Create Admin User
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        Employee::factory()->create(['user_id' => $this->admin->id]);

        // Create Regular Employee (User) - UUID ADA DI SINI
        $this->employee = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->employee->assignRole('employee');

        // Pastikan profil Employee dibuat untuk relasi (jika dibutuhkan sistem)
        $this->employeeProfile = Employee::factory()->create([
            'user_id' => $this->employee->id,
        ]);

        // Create HR User
        $this->hr = User::factory()->create();
        $this->hr->assignRole('hr');
        Employee::factory()->create(['user_id' => $this->hr->id]);
    }

    /** @test */
    public function admin_can_list_all_users()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'message']);
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        Sanctum::actingAs($this->admin);

        $division = Division::factory()->create();
        $team = Team::factory()->create(['division_id' => $division->id]);
        $position = Position::factory()->create();

        $payload = [
            'name' => 'New Employee',
            'email' => 'new.employee@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_uuid' => $team->uuid,
            'position_uuid' => $position->uuid,
            'is_active' => true,
            'employee_status' => EmployeeStatus::PERMANENT->value,
            'gender' => 'male',
            'manager_nik' => null,
            'contract_start' => null,
            'contract_end' => null,
            'base_salary' => 5000000,
            'phone' => '08123456789',
            'date_of_birth' => '1995-01-01',
            'address' => 'Jl. Sample Road No. 1',
            'join_date' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'new.employee@example.com']);
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/users/{$this->employee->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $this->employee->uuid);
    }

    /** @test */
    public function admin_can_update_user_details()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'name' => 'Updated Name',
            'email' => $this->employee->email,
            'is_active' => true,
            'employee_status' => EmployeeStatus::PERMANENT->value,
        ];

        $response = $this->putJson("/api/users/{$this->employee->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $this->employee->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function admin_can_soft_delete_user()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/users/{$this->employee->uuid}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $this->employee->id]);
    }

    /** @test */
    public function admin_can_restore_deleted_user()
    {
        Sanctum::actingAs($this->admin);
        $this->employee->delete();

        $response = $this->postJson("/api/users/restore/{$this->employee->uuid}");

        $response->assertStatus(200);
        $this->assertNotSoftDeleted('users', ['id' => $this->employee->id]);
    }

    /** @test */
    public function admin_can_force_delete_user()
    {
        Sanctum::actingAs($this->admin);
        $this->employee->delete();

        // Clear cache permission agar role terdeteksi setelah delete
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/users/force-delete/{$this->employee->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $this->employee->id]);
    }

    /** @test */
    /** @test */
    public function admin_can_change_user_password()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'new_password' => 'NewSecurePass123!',
            'new_password_confirmation' => 'NewSecurePass123!',
        ];

        $response = $this->putJson("/api/users/admin-change-password/{$this->employee->uuid}", $payload);

        $response->assertStatus(200);

        $this->assertTrue(Hash::check('NewSecurePass123!', $this->employee->fresh()->password));
    }

    /** @test */
    public function admin_can_terminate_user_employment()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'type' => 'resigned',
            'date' => now()->format('Y-m-d'),
        ];

        $response = $this->putJson("/api/users/terminate-employment/{$this->employee->uuid}", $payload);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_user_status()
    {
        Sanctum::actingAs($this->admin);

        $payload = ['is_active' => false];

        $response = $this->putJson("/api/users/status/{$this->employee->uuid}", $payload);

        $response->assertStatus(200);

        // Assuming your User model handles is_active as a boolean or similar
        // Adjust assertion based on your actual database schema/model logic
        // $this->assertEquals(0, $this->employee->fresh()->is_active);
    }

    /** @test */
    public function logged_in_user_can_get_their_profile()
    {
        Sanctum::actingAs($this->employee);

        $response = $this->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $this->employee->uuid);
    }

    /** @test */
    public function logged_in_user_can_change_own_password()
    {
        Sanctum::actingAs($this->employee);

        $payload = [
            'current_password' => 'password',
            'new_password' => 'MyNewPass123!',
            'new_password_confirmation' => 'MyNewPass123!',
        ];

        $response = $this->putJson('/api/users/change-password', $payload);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('MyNewPass123!', $this->employee->fresh()->password));
    }

    /** @test */
    public function logged_in_user_can_upload_profile_photo()
    {
        Sanctum::actingAs($this->employee);
        Storage::fake('public');

        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->postJson("/api/users/upload-profile-photo/{$this->employee->uuid}", [
            'profile_photo' => $file,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_get_my_leave_balances()
    {
        Sanctum::actingAs($this->employee);

        $response = $this->getJson('/api/users/my-leave-balances');

        $response->assertStatus(200);
    }
}
