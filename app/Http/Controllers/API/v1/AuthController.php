<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['nullable', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::created([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'User registered successfully');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => ['required', 'string'], // Can be username, email, or phone_number
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $identifier = $request->string('identifier')->toString();

        // Attempt to find the user by username, email, or phone_number
        $user = User::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('phone_number', $identifier)
            ->first();

        if (! $user) {
            return ApiResponse::error('Invalid credentials', null, 401);
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return ApiResponse::error('Account temporarily locked. Try again later.', null, 423);
        }

        // Check if the provided password matches the stored hashed password
        if (! Hash::check($request->password, $user->password)) {
            $attempts = $user->failed_login_attempts + 1;
            $lock = $attempts >= 5;

            $user->forceFill([
                'failed_login_attempts' => $lock ? 0 : $attempts,
                'locked_until' => $lock ? now()->addMinutes(15) : null,
            ])->save();

            return ApiResponse::error('Invalid credentials', null, 401);
        }

        // Reset failed login attempts and locked_until on successful login
        if (! $user->is_active) {
            return ApiResponse::error('Account is deactivated', null, 403);
        }

        // Reset failed login attempts and locked_until on successful login
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        // Create a new personal access token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return the user data and token in the response
        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout successful');
    }

    public function me(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()), 'User profile retrieved');
    }
}
