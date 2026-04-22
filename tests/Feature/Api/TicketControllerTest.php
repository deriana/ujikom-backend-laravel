<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employeeUser;
    protected Employee $employeeProfile;
    protected User $helpdeskUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');
        Role::findOrCreate('helpdesk', 'api');

        // 1.1 Setup Permissions
        Permission::findOrCreate('ticketing.index', 'api');
        Permission::findOrCreate('ticketing.show', 'api');
        Permission::findOrCreate('ticketing.create', 'api');
        Permission::findOrCreate('ticketing.edit', 'api');
        Permission::findOrCreate('ticketing.destroy', 'api');
        Permission::findOrCreate('ticketing.export', 'api');
        Permission::findOrCreate('ticketing.reply', 'api');
        Permission::findOrCreate('ticketing.status', 'api');
        Permission::findOrCreate('ticketing.assign', 'api');
        Permission::findOrCreate('ticketing.rate', 'api');
        Permission::findOrCreate('ticketing.dashboard', 'api');

        // 2. Create Admin User (Tanpa profil Employee untuk simulasi case real)
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo(Permission::all());

        // 3. Create Target Employee (Yang akan dinilai)
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');
        $this->employeeUser->givePermissionTo(['ticketing.index', 'ticketing.show', 'ticketing.edit', 'ticketing.destroy', 'ticketing.create', 'ticketing.reply', 'ticketing.rate']);
        $this->employeeProfile = Employee::factory()->create(['user_id' => $this->employeeUser->id]);

        // Additional setup for Ticket tests
        $this->helpdeskUser = User::factory()->create();
        $this->helpdeskUser->assignRole('helpdesk');
        $this->helpdeskUser->givePermissionTo(['ticketing.index', 'ticketing.show', 'ticketing.reply', 'ticketing.status', 'ticketing.assign', 'ticketing.dashboard']);
        Employee::factory()->create(['user_id' => $this->helpdeskUser->id]);

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test */
    public function it_can_fetch_all_tickets()
    {
        Sanctum::actingAs($this->admin);

        for ($i = 0; $i < 3; $i++) {
            Ticket::create([
                'reporter_id' => $this->employeeProfile->id,
                'subject' => 'Test Subject ' . $i,
                'description' => 'Test Description ' . $i,
                'priority' => 'low',
                'status' => 'open'
            ]);
        }

        $response = $this->getJson('/api/ticketing');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'message'])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function employee_can_create_a_ticket()
    {
        Sanctum::actingAs($this->employeeUser);

        $payload = [
            'subject' => 'Issue with login',
            'description' => 'I cannot login to the system since yesterday.',
            'priority' => 'high',
        ];

        $response = $this->postJson('/api/ticketing', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.subject', 'Issue with login')
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Issue with login',
            'reporter_id' => $this->employeeProfile->id,
            'status' => 'open',
        ]);
    }

    /** @test */
    public function user_can_view_ticket_details()
    {
        Sanctum::actingAs($this->admin);

        $ticket = Ticket::create([
            'reporter_id' => $this->employeeProfile->id,
            'subject' => 'Network Issue',
            'description' => 'Cannot connect to wifi',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $response = $this->getJson("/api/ticketing/{$ticket->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $ticket->uuid)
            ->assertJsonPath('data.subject', 'Network Issue');
    }

    /** @test */
    public function employee_can_update_their_open_ticket()
    {
        Sanctum::actingAs($this->employeeUser);

        $ticket = Ticket::create([
            'reporter_id' => $this->employeeProfile->id,
            'subject' => 'Old Subject',
            'description' => 'Cannot connect to wifi',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $payload = [
            'subject' => 'Updated Subject',
        ];

        $response = $this->putJson("/api/ticketing/{$ticket->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.subject', 'Updated Subject');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'Updated Subject',
        ]);
    }

    /** @test */
    public function employee_cannot_update_closed_ticket()
    {
        Sanctum::actingAs($this->employeeUser);

        $ticket = Ticket::create([
            'reporter_id' => $this->employeeProfile->id,
            'subject' => 'Old Subject',
            'description' => 'Cannot connect to wifi',
            'priority' => 'high',
            'status' => 'closed',
        ]);

        $payload = [
            'subject' => 'Updated Subject',
        ];

        $response = $this->putJson("/api/ticketing/{$ticket->uuid}", $payload);

        // DomainException caught and returned as 400
        $response->assertStatus(400);
    }

    /** @test */
    public function admin_can_delete_open_ticket()
    {
        Sanctum::actingAs($this->admin);

        $ticket = Ticket::create([
            'reporter_id' => $this->employeeProfile->id,
            'subject' => 'Old Subject',
            'description' => 'Cannot connect to wifi',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $response = $this->deleteJson("/api/ticketing/{$ticket->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }

    /** @test */
    public function helpdesk_can_reply_to_ticket()
    {
        Sanctum::actingAs($this->helpdeskUser);

        $ticket = Ticket::create([
            'reporter_id' => $this->employeeProfile->id,
            'subject' => 'Old Subject',
            'description' => 'Cannot connect to wifi',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $payload = [
            'response' => 'We are working on this issue right now.',
        ];

        $response = $this->postJson("/api/ticketing/{$ticket->uuid}/reply", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ticket_responses', [
            'ticket_id' => $ticket->id,
            'responder_id' => $this->helpdeskUser->id,
            'response' => 'We are working on this issue right now.',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in progress',
        ]);
    }

    /** @test */
    public function employee_can_rate_closed_ticket()
    {
        Sanctum::actingAs($this->employeeUser);

        $ticket = Ticket::create([
            'reporter_id' => $this->employeeProfile->id,
            'subject' => 'Old Subject',
            'description' => 'Cannot connect to wifi',
            'priority' => 'high',
            'status' => 'closed',
        ]);

        $payload = [
            'rating' => 5,
            'feedback' => 'Great support!',
        ];

        $response = $this->postJson("/api/ticketing/{$ticket->uuid}/rate", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('satisfaction_ratings', [
            'ticket_id' => $ticket->id,
            'employee_id' => $this->employeeProfile->id,
            'rating' => 5,
            'feedback' => 'Great support!',
        ]);
    }

    /** @test */
    public function admin_can_update_ticket_status()
    {
        Sanctum::actingAs($this->admin);

        $ticket = Ticket::create([
            'reporter_id' => $this->employeeProfile->id,
            'subject' => 'Old Subject',
            'description' => 'Cannot connect to wifi',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $payload = [
            'status' => 'in progress',
        ];

        $response = $this->putJson("/api/ticketing/{$ticket->uuid}/status", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in progress');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in progress',
        ]);
    }

    /** @test */
    public function user_can_fetch_dashboard_data()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/ticketing/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'total',
                        'open',
                        'in_progress',
                        'resolved',
                        'closed',
                        'average_rating',
                    ],
                    'recent_tickets',
                ]
            ]);
    }
}
