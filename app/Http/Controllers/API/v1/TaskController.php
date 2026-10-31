<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\TaskResource;
use App\Helpers\ApiResponse;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->priority))
            ->when($request->boolean('completed'), fn($q) => $q->where('is_completed', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($tasks, 'Tasks retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'status'              => ['sometimes', 'string', 'in:pending,in_progress,completed'],
            'priority'            => ['sometimes', 'string', 'in:low,medium,high'],
            'due_date'            => ['sometimes', 'date'],
            'scheduled_start_at'  => ['sometimes', 'date'],
            'scheduled_end_at'    => ['sometimes', 'date'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $task = Task::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new TaskResource($task), 'Task created');
    }

    public function show(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new TaskResource($task), 'Task retrieved');
    }

    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'               => ['sometimes', 'string', 'max:255'],
            'description'         => ['sometimes', 'string'],
            'status'              => ['sometimes', 'string', 'in:pending,in_progress,completed'],
            'priority'            => ['sometimes', 'string', 'in:low,medium,high'],
            'due_date'            => ['sometimes', 'date'],
            'scheduled_start_at'  => ['sometimes', 'date'],
            'scheduled_end_at'    => ['sometimes', 'date'],
            'is_completed'        => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        if ($request->boolean('is_completed') && !$task->is_completed) {
            $validator->validated()['completed_at'] = now();
        }

        $task->update($validator->validated());
        return ApiResponse::success(new TaskResource($task), 'Task updated');
    }

    public function destroy(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $task->delete();
        return ApiResponse::success(null, 'Task deleted');
    }
}
