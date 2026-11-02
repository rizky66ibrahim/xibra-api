<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TelegramBridgeController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ],
        ], 'Telegram account linked');
    }

    public function tasks(Request $request)
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->where('is_completed', false)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return ApiResponse::success(
            $tasks->map(fn(Task $task) => $this->present($task))->values(),
            'Tasks retrieved'
        );
    }

    public function storeTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $task = Task::create(array_merge($validator->validated(), [
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'is_completed' => false,
        ]));

        return ApiResponse::created($this->present($task), 'Task created');
    }

    public function completeTask(Request $request, Task $task)
    {
        // 404, bukan 403, supaya bot tidak bisa menebak keberadaan tugas user lain
        if ($task->user_id !== $request->user()->id) {
            return ApiResponse::error('Task not found', null, 404);
        }

        if (! $task->is_completed) {
            $task->update([
                'status' => 'completed',
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        return ApiResponse::success($this->present($task->fresh()), 'Task completed');
    }

    private function present(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'is_completed' => (bool) $task->is_completed,
        ];
    }
}
