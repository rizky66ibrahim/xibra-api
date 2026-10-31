<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserResource;
use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => ['required', 'string', 'max:255'],
            'username'     => ['required', 'string', 'max:255', 'unique:users'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['nullable', 'string', 'max:20', 'unique:users'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'role'         => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $user = User::create([
            'name'         => $request->name,
            'username'     => $request->username,
            'email'        => $request->email,
            'phone_number' => $request->phone_number,
            'password'     => Hash::make($request->password),
            'role'         => $request->role ?? 'user',
            'is_active'    => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::created([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 'User registered successfully');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => ['required', 'string'], // username / email / phone_number
            'password'   => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $identifier = $request->identifier;
        $password = $request->password;

        // Find user by username, email, or phone_number
        $user = User::where(function ($query) use ($identifier) {
            $query->where('name', $identifier)
                ->orWhere('username', $identifier)
                ->orWhere('email', $identifier)
                ->orWhere('phone_number', $identifier);
        })->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return ApiResponse::error('Invalid credentials', null, 401);
        }

        if (!$user->is_active) {
            return ApiResponse::error('Account is deactivated', null, 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user'  => new UserResource($user),
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
