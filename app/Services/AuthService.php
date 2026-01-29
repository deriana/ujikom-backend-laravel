<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use DomainException;

class AuthService
{
    /**
     * Register a new user and return user + token
     *
     * @param array $data
     * @return array [$user, $token]
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [$user, $token];
        });
    }

    /**
     * Authenticate user and return user + token
     *
     * @param array $data
     * @return array [$user, $token]
     * @throws DomainException
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new DomainException('The email or password is incorrect.');
        }

        if (!$user->is_active) {
            throw new DomainException('Your account is inactive. Please contact support.');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [$user, $token];
    }

    /**
     * Logout user by deleting current access token
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        return (bool) $user->currentAccessToken()?->delete();
    }
}
