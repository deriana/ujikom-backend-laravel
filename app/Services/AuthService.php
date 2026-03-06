<?php

namespace App\Services;

use App\Exceptions\UserNotVerifiedException;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Register a new user and generate an authentication token.
     *
     * @param array $data
     * @return array [$user, $token]
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create a new user record in the database
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // 2. Generate a plain-text Sanctum token for the new user
            $token = $user->createToken('auth_token')->plainTextToken;

            return [$user, $token];
        });
    }

    /**
     * Authenticate user credentials and return user profile with token.
     *
     * @param array $data
     * @return array [$user, $token]
     * @throws DomainException|UserNotVerifiedException
     */
    public function login(array $data): array
    {
        // 1. Find the user by email address
        $user = User::where('email', $data['email'])->first();

        // 2. Validate user existence and password hash
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new DomainException('The email or password is incorrect.');
        }

        // 3. Check if the account status is active
        if (! $user->is_active) {
            throw new DomainException('Your account is inactive. Please contact support.');
        }

        // 4. Ensure the user has verified their email address
        if (! $user->is_verified) {
            throw new UserNotVerifiedException($user->email);
        }

        // 5. Eager load employee-related relationships for the response
        $user->load(['employee', 'employee.position', 'employee.team.division', 'employee.team', 'employee.manager']);

        // 6. Create a new access token for the session
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$user, $token];
    }

    /**
     * Revoke the user's current access token to perform logout.
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        // 1. Delete the specific token used for the current request
        return (bool) $user->currentAccessToken()?->delete();
    }
}
