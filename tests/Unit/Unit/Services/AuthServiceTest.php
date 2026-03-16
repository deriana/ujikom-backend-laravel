<?php

namespace Tests\Unit\Services;

use App\Exceptions\UserNotVerifiedException;
use App\Models\User;
use App\Services\AuthService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase; // Perlu karena Service ini menulis ke database

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /** --- TEST REGISTER --- **/

    public function test_register_creates_user_and_returns_token()
    {
        $data = [
            'name' => 'Deri Maruf',
            'email' => 'deri@example.com',
            'password' => 'secret123'
        ];

        [$user, $token] = $this->authService->register($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Deri Maruf', $user->name);
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('users', ['email' => 'deri@example.com']);
    }

    /** --- TEST LOGIN --- **/

    public function test_login_success_when_user_is_active_and_verified()
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'is_active' => true,
            'is_verified' => true
        ]);

        [$loggedUser, $token] = $this->authService->login([
            'email' => $user->email,
            'password' => $password
        ]);

        $this->assertEquals($user->id, $loggedUser->id);
        $this->assertNotEmpty($token);
    }

    public function test_login_throws_exception_if_password_wrong()
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct_password'),
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('The email or password is incorrect.');

        $this->authService->login([
            'email' => $user->email,
            'password' => 'wrong_password'
        ]);
    }

    public function test_login_throws_exception_if_user_inactive()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'is_active' => false, // Inactive
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Your account is inactive. Please contact support.');

        $this->authService->login([
            'email' => $user->email,
            'password' => 'password123'
        ]);
    }

    public function test_login_throws_exception_if_user_not_verified()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'is_active' => true,
            'is_verified' => false, // Not Verified
        ]);

        $this->expectException(UserNotVerifiedException::class);

        $this->authService->login([
            'email' => $user->email,
            'password' => 'password123'
        ]);
    }

    /** --- TEST LOGOUT --- **/

    public function test_logout_revokes_current_token()
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('test_token');

        // Set current access token secara manual untuk simulasi
        $user->withAccessToken($tokenResult->accessToken);

        $result = $this->authService->logout($user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenResult->accessToken->id
        ]);
    }
}
