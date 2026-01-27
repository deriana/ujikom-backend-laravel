<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function register(array $data)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();
            return [$user, $token];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Registration Error: ' . $e->getMessage());
            throw new Exception('Registration failed. Please try again.');
        }
    }

    public function login(array $data)
    {
        try {
            $user = User::where('email', $data['email'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                throw new Exception('The Email or Password is incorrect.');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return [$user, $token];
        } catch (Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function logout(User $user)
    {
        try {
            return $user->currentAccessToken()->delete();
        } catch (Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            throw new Exception('Logout failed. Please try again.');
        }
    }
}
