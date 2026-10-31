<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ProjectResource;
use App\Helpers\ApiResponse;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('user_id', $request->user()->id)
            ->when($request->boolean('featured'), fn($q) => $q->where('is_featured', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($projects, 'Projects retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url'   => ['nullable', 'url', 'max:500'],
            'github_url'  => ['nullable', 'url', 'max:500'],
            'live_url'    => ['nullable', 'url', 'max:500'],
            'start_date'  => ['sometimes', 'date'],
            'end_date'    => ['sometimes', 'date'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_public'   => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $project = Project::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new ProjectResource($project), 'Project created');
    }

    public function show(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new ProjectResource($project), 'Project retrieved');
    }

    public function update(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'image_url'   => ['sometimes', 'url', 'max:500'],
            'github_url'  => ['sometimes', 'url', 'max:500'],
            'live_url'    => ['sometimes', 'url', 'max:500'],
            'start_date'  => ['sometimes', 'date'],
            'end_date'    => ['sometimes', 'date'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_public'   => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $project->update($validator->validated());
        return ApiResponse::success(new ProjectResource($project), 'Project updated');
    }

    public function destroy(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $project->delete();
        return ApiResponse::success(null, 'Project deleted');
    }
}
