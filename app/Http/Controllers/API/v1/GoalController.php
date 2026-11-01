<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\GoalResource;
use App\Helpers\ApiResponse;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        $goals = Goal::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return ApiResponse::success($goals, 'Goals retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'emoji'         => ['sometimes', 'string', 'max:10'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'target_date'   => ['sometimes', 'date'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $goal = Goal::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id, 'current_amount' => 0]
        ));

        return ApiResponse::created(new GoalResource($goal), 'Goal created');
    }

    public function show(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new GoalResource($goal), 'Goal retrieved');
    }

    public function update(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'         => ['sometimes', 'string', 'max:255'],
            'description'   => ['sometimes', 'string'],
            'emoji'         => ['sometimes', 'string', 'max:10'],
            'target_amount' => ['sometimes', 'numeric', 'min:0'],
            'current_amount' => ['sometimes', 'numeric', 'min:0'],
            'target_date'   => ['sometimes', 'date'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $goal->update($validator->validated());
        return ApiResponse::success(new GoalResource($goal), 'Goal updated');
    }

    public function destroy(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $goal->delete();
        return ApiResponse::success(null, 'Goal deleted');
    }
}
