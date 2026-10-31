<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ProfileResource;
use App\Helpers\ApiResponse;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = Profile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found', null, 404);
        }

        return ApiResponse::success(new ProfileResource($profile), 'Profile retrieved successfully');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bio'           => ['nullable', 'string', 'max:1000'],
            'birth_date'    => ['nullable', 'date'],
            'gender'        => ['nullable', 'string', 'max:20'],
            'address'       => ['nullable', 'string', 'max:500'],
            'city'          => ['nullable', 'string', 'max:100'],
            'province'      => ['nullable', 'string', 'max:100'],
            'postal_code'   => ['nullable', 'string', 'max:20'],
            'country'       => ['nullable', 'string', 'max:50'],
            'website'       => ['nullable', 'url', 'max:255'],
            'social_media'  => ['nullable', 'array'],
            'timezone'      => ['nullable', 'string', 'max:50'],
            'language'      => ['nullable', 'string', 'max:10'],
            'avatar_url'    => ['nullable', 'url', 'max:500'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $existing = Profile::where('user_id', $request->user()->id)->first();
        if ($existing) {
            return ApiResponse::error('Profile already exists', null, 409);
        }

        $profile = Profile::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new ProfileResource($profile), 'Profile created successfully');
    }

    public function update(Request $request)
    {
        $profile = Profile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'bio'           => ['sometimes', 'string', 'max:1000'],
            'birth_date'    => ['sometimes', 'date'],
            'gender'        => ['sometimes', 'string', 'max:20'],
            'address'       => ['sometimes', 'string', 'max:500'],
            'city'          => ['sometimes', 'string', 'max:100'],
            'province'      => ['sometimes', 'string', 'max:100'],
            'postal_code'   => ['sometimes', 'string', 'max:20'],
            'country'       => ['sometimes', 'string', 'max:50'],
            'website'       => ['sometimes', 'url', 'max:255'],
            'social_media'  => ['sometimes', 'array'],
            'timezone'      => ['sometimes', 'string', 'max:50'],
            'language'      => ['sometimes', 'string', 'max:10'],
            'avatar_url'    => ['sometimes', 'url', 'max:500'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $profile->update($validator->validated());

        return ApiResponse::success(new ProfileResource($profile), 'Profile updated successfully');
    }

    public function destroy(Request $request)
    {
        $profile = Profile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found', null, 404);
        }

        $profile->delete();

        return ApiResponse::success(null, 'Profile deleted successfully');
    }
}
