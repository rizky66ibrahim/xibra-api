<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SkillResource;
use App\Helpers\ApiResponse;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SkillController extends Controller
{
    public function index(Request $request)
    {
        $skills = Skill::where('user_id', $request->user()->id)
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($skills, 'Skills retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'               => ['required', 'string', 'max:100'],
            'category'           => ['sometimes', 'string', 'max:50'],
            'level'              => ['sometimes', 'string', 'max:20'],
            'years_of_experience' => ['sometimes', 'integer', 'min:0'],
            'is_public'          => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $skill = Skill::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new SkillResource($skill), 'Skill created');
    }

    public function show(Request $request, Skill $skill)
    {
        if ($skill->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new SkillResource($skill), 'Skill retrieved');
    }

    public function update(Request $request, Skill $skill)
    {
        if ($skill->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'name'               => ['sometimes', 'string', 'max:100'],
            'category'           => ['sometimes', 'string', 'max:50'],
            'level'              => ['sometimes', 'string', 'max:20'],
            'years_of_experience' => ['sometimes', 'integer', 'min:0'],
            'is_public'          => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $skill->update($validator->validated());
        return ApiResponse::success(new SkillResource($skill), 'Skill updated');
    }

    public function destroy(Request $request, Skill $skill)
    {
        if ($skill->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $skill->delete();
        return ApiResponse::success(null, 'Skill deleted');
    }
}
