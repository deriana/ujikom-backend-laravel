<?php

namespace Tests\Unit\Http\Controllers\Api;

use Tests\TestCase;
use App\Models\User;
use App\Services\AuthService;
use App\Http\Controllers\Api\AuthController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class AuthControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $authServiceMock;
    protected $authController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->authController = new AuthController($this->authServiceMock);
    }

    /**
     * Helper untuk membuat Mock FormRequest yang valid
     */
    protected function createMockRequest(string $class, array $data)
    {
        $request = new $class();
        $request->replace($data);

        // Membuat validator manual agar method validated() tidak null
        $validator = Validator::make($data, []);
        $request->setValidator($validator);

        return $request;
    }

    public function test_register_returns_success_response()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ];

        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $token = 'mock-token-123';

        $this->authServiceMock
            ->shouldReceive('register')
            ->once()
            ->andReturn([$user, $token]);

        // Gunakan helper untuk membuat request
        $request = $this->createMockRequest(RegisterRequest::class, $data);

        $response = $this->authController->register($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_login_returns_user_and_token()
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];
        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $token = 'mock-token-123';

        $this->authServiceMock
            ->shouldReceive('login')
            ->once()
            ->andReturn([$user, $token]);

        // Gunakan helper untuk membuat request
        $request = $this->createMockRequest(LoginRequest::class, $credentials);

        $response = $this->authController->login($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_logout_calls_service_and_returns_success()
    {
        $user = new User(['id' => 1]);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $this->authServiceMock->shouldReceive('logout')->once()->with($user);

        $response = $this->authController->logout();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
