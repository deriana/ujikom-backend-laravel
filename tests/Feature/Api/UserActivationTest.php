<?php

namespace Tests\Feature\Api;

use App\Mail\VerifyEmail;
use App\Models\User;
use App\Models\VerificationToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_check_valid_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'is_verified' => false]);
        $plainToken = 'test-token-123';
        VerificationToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        $response = $this->getJson("/api/auth/check-token?token={$plainToken}");

        $response->assertStatus(200)
            ->assertJson([
                'email' => 'test@example.com',
                'message' => 'Token is valid.',
            ]);
    }

    public function test_cannot_check_invalid_token()
    {
        $response = $this->getJson('/api/auth/check-token?token=invalid-token');

        $response->assertStatus(410)
            ->assertJson(['message' => 'Invalid or expired token.']);
    }

    public function test_cannot_check_expired_token()
    {
        $user = User::factory()->create();
        $plainToken = 'expired-token';
        VerificationToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => Carbon::now()->subHour(),
        ]);

        $response = $this->getJson("/api/auth/check-token?token={$plainToken}");

        $response->assertStatus(410)
            ->assertJson(['message' => 'Invalid or expired token.']);
    }

    public function test_can_finalize_activation()
    {
        $user = User::factory()->create(['is_verified' => false, 'email_verified_at' => null]);
        $plainToken = 'activation-token';
        VerificationToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        $response = $this->postJson('/api/auth/finalize-activation', [
            'token' => $plainToken,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Account activated successfully.']);

        $user->refresh();
        $this->assertTrue($user->is_verified);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertDatabaseMissing('verification_tokens', ['user_id' => $user->id]);
    }
}
