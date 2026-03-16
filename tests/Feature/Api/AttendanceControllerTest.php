<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('employee', 'api');
        Role::findOrCreate('hr', 'api');
    }

    /** @test */
    public function it_validates_single_attendance_request()
    {
        // Act
        $response = $this->postJson('/api/attendance/single-send', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['descriptor', 'photo', 'latitude', 'longitude']);
    }

    /** @test */
    public function it_handles_successful_single_attendance()
    {
        // Arrange
        $this->mock(AttendanceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('handleAttendance')
                ->once()
                ->withArgs(function ($payload, $userAgent) {
                    return $payload['descriptor'] === 'test-descriptor' &&
                           $payload['latitude'] == -6.200 &&
                           $payload['longitude'] == 106.816 &&
                           $payload['photo'] instanceof UploadedFile;
                })
                ->andReturn([
                    'success' => true,
                    'message' => 'Attendance recorded successfully',
                    'data' => ['id' => 1, 'status' => 'present']
                ]);
        });

        $payload = [
            'descriptor' => 'test-descriptor',
            'latitude' => -6.200,
            'longitude' => 106.816,
            'photo' => UploadedFile::fake()->image('face.jpg'),
        ];

        // Act
        $response = $this->postJson('/api/attendance/single-send', $payload);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Attendance recorded successfully',
                'data' => ['id' => 1, 'status' => 'present']
            ]);
    }

    /** @test */
    public function it_handles_failed_single_attendance_service_response()
    {
        // Arrange
        $this->mock(AttendanceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('handleAttendance')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Face not recognized or outside radius',
                ]);
        });

        $payload = [
            'descriptor' => 'test-descriptor',
            'latitude' => -6.200,
            'longitude' => 106.816,
            'photo' => UploadedFile::fake()->image('face.jpg'),
        ];

        // Act
        $response = $this->postJson('/api/attendance/single-send', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Face not recognized or outside radius',
            ]);
    }

    /** @test */
    public function it_validates_bulk_attendance_request()
    {
        // Act
        $response = $this->postJson('/api/attendance/bulk-send', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attendances', 'latitude', 'longitude']);
    }

    /** @test */
    public function it_handles_successful_bulk_attendance()
    {
        // Arrange
        $this->mock(AttendanceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('handleBulkAttendance')
                ->once()
                ->andReturn([
                    'summary' => [
                        'total' => 2,
                        'success' => 2,
                        'failed' => 0
                    ]
                ]);
        });

        $payload = [
            'latitude' => -6.200,
            'longitude' => 106.816,
            'attendances' => [
                [
                    'descriptor' => 'desc-1',
                    'photo' => UploadedFile::fake()->image('face1.jpg'),
                ],
                [
                    'descriptor' => 'desc-2',
                    'photo' => UploadedFile::fake()->image('face2.jpg'),
                ]
            ]
        ];

        // Act
        $response = $this->postJson('/api/attendance/bulk-send', $payload);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Bulk Attendance Processed',
                'data' => [
                    'total' => 2,
                    'success' => 2,
                    'failed' => 0
                ]
            ]);
    }

    /** @test */
    public function it_handles_successful_manual_attendance()
    {
        // Arrange
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->mock(AttendanceService::class, function (MockInterface $mock) use ($employee) {
            $mock->shouldReceive('executeProcessManual')
                ->once()
                ->withArgs(function ($emp, $payload, $userAgent) use ($employee) {
                    return $emp->id === $employee->id &&
                           $payload['reason'] === 'Camera broken' &&
                           $payload['latitude'] == -6.200 &&
                           $payload['attachment'] instanceof UploadedFile;
                })
                ->andReturn([
                    'success' => true,
                    'message' => 'Manual attendance submitted',
                    'data' => ['id' => 1, 'type' => 'manual']
                ]);
        });

        $payload = [
            'reason' => 'Camera broken',
            'latitude' => -6.200,
            'longitude' => 106.816,
            'attachment' => UploadedFile::fake()->image('proof.jpg'),
        ];

        // Act
        $response = $this->postJson('/api/attendance/manual-send', $payload);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Manual attendance submitted',
                'data' => ['id' => 1, 'type' => 'manual']
            ]);
    }

    /** @test */
    public function it_handles_failed_manual_attendance_service_response()
    {
        // Arrange
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->mock(AttendanceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('executeProcessManual')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'You have already clocked in today',
                ]);
        });

        $payload = [
            'reason' => 'Camera broken',
            'latitude' => -6.200,
            'longitude' => 106.816,
            'attachment' => UploadedFile::fake()->image('proof.jpg'),
        ];

        // Act
        $response = $this->postJson('/api/attendance/manual-send', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'You have already clocked in today',
            ]);
    }

    /** @test */
    public function it_gets_attendance_status_today_for_authenticated_employee()
    {
        // Arrange
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $this->mock(AttendanceService::class, function (MockInterface $mock) use ($employee) {
            $mock->shouldReceive('getTodayAttendanceStatus')
                ->once()
                ->withArgs(function ($emp) use ($employee) {
                    return $emp->id === $employee->id;
                })
                // Harus kembali ke json_encode karena Service mengharuskan return string
                ->andReturn(json_encode([
                    'clock_in' => '08:00',
                    'clock_out' => null,
                    'status' => 'present',
                ]));
        });

        // Act
        $response = $this->getJson('/api/attendances/today');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Today attendance status retrieved',
            'data' => [
                'status' => '{"clock_in":"08:00","clock_out":null,"status":"present"}',
            ],
        ]);
    }

    /** @test */
    public function it_returns_404_if_user_has_no_employee_profile_for_status_today()
    {
        // Arrange
        $user = User::factory()->create();
        // No employee record created

        Sanctum::actingAs($user, ['*']);

        // Act
        $response = $this->getJson('/api/attendances/today');

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Employee profile not found.'
            ]);
    }
}
