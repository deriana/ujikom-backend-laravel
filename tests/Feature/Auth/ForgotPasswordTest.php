<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\PasswordResetToken;
use App\Mail\PasswordResetMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_link()
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'If this email is registered, a password reset link has been sent.']);

        Mail::assertQueued(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $this->assertDatabaseHas('password_resets', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_reset_password_with_invalid_token()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(410);
    }

    public function test_user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create();
        $plainToken = bin2hex(random_bytes(32));

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $plainToken,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Password has been reset successfully.']);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));

        $this->assertDatabaseMissing('password_resets', [
            'user_id' => $user->id,
        ]);
    }

    public function test_check_token_endpoint_returns_success_for_valid_token()
    {
        $user = User::factory()->create();
        $plainToken = bin2hex(random_bytes(32));

        PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/auth/reset-password/check?token=' . $plainToken);

        $response->assertStatus(200);
        $response->assertJson([
            'email' => $user->email,
            'message' => 'Token is valid.',
        ]);
    }
}
