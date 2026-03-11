<?php

namespace Tests\Feature\Api;

use App\Exports\AttendancesExport;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceDetailControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');

        // Setup Users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('employee');
    }

    /** @test */
    public function it_can_list_attendances_with_date_filters()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $attendances = Attendance::factory()->count(3)->make();
        $filters = ['start_date' => '2023-01-01', 'end_date' => '2023-01-31'];

        $this->mock(AttendanceDetailService::class, function (MockInterface $mock) use ($attendances, $filters) {
            $mock->shouldReceive('index')
                ->once()
                ->with($filters)
                ->andReturn($attendances);
        });

        $response = $this->getJson('/api/attendances?' . http_build_query($filters));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ]);
    }

    /** @test */
    public function it_can_show_specific_attendance_detail()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $attendance = Attendance::factory()->create();

        $this->mock(AttendanceDetailService::class, function (MockInterface $mock) use ($attendance) {
            $mock->shouldReceive('show')
                ->once()
                // We check that the service receives an instance of Attendance
                ->with(\Mockery::type(Attendance::class))
                ->andReturn($attendance);
        });

        $response = $this->getJson("/api/attendances/{$attendance->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $attendance->id);
    }

    /** @test */
    public function it_returns_404_if_attendance_not_found()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Uses default Laravel implicit binding behavior
        $response = $this->getJson("/api/attendances/99999");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_export_attendances_to_excel()
    {
        Excel::fake();
        Sanctum::actingAs($this->admin, ['*']);

        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/attendances/export?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);

        $expectedFileName = "attendances_{$startDate}_to_{$endDate}.xlsx";

        Excel::assertDownloaded($expectedFileName, function (AttendancesExport $export) {
            return true; // Additional assertions on the Export object can go here
        });
    }

    /** @test */
    public function it_validates_export_date_range()
    {
        Sanctum::actingAs($this->admin, ['*']);

        // End date before start date
        $response = $this->getJson('/api/attendances/export?start_date=2023-01-31&end_date=2023-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_forbids_unauthorized_user_from_exporting()
    {
        // Assuming only admins can export
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/attendances/export');

        $response->assertStatus(403);
    }
}
