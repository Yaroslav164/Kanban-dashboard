<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * List the user's tasks with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::where('user_id', $request->user()->id);

        if ($request->filled('completed')) {
            $query->where('completed', filter_var($request->input('completed'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('q')) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->input('q')).'%';
            $query->where(fn ($q) => $q
                ->where('title', 'like', $like)
                ->orWhere('description', 'like', $like));
        }

        $tasks = $query->orderByDesc('completed')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    /**
     * Create a task.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;

        return response()->json(['data' => Task::create($data)], 201);
    }

    /**
     * Update a task.
     */
    public function update(Request $request, int $task): JsonResponse
    {
        $task = $this->ownedTask($request, $task);
        $task->update($this->validated($request, isUpdate: true));

        return response()->json(['data' => $task->refresh()]);
    }

    /**
     * Toggle the completed flag.
     */
    public function toggle(Request $request, int $task): JsonResponse
    {
        $task = $this->ownedTask($request, $task);
        $task->completed = ! $task->completed;
        $task->save();

        return response()->json(['data' => $task->refresh()]);
    }

    /**
     * Delete a task.
     */
    public function destroy(Request $request, int $task): JsonResponse
    {
        $this->ownedTask($request, $task)->delete();

        return response()->json(['message' => 'Задача удалена.']);
    }

    /**
     * Find a task owned by the authenticated user.
     */
    private function ownedTask(Request $request, int $task): Task
    {
        return Task::where('user_id', $request->user()->id)
            ->findOrFail($task);
    }

    /**
     * Validate task input.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $isUpdate = false): array
    {
        $rules = $isUpdate
            ? [
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'completed' => ['sometimes', 'boolean'],
                'priority' => ['sometimes', 'required', 'in:low,medium,high'],
                'due_date' => ['nullable', 'date'],
            ]
            : [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'completed' => ['sometimes', 'boolean'],
                'priority' => ['sometimes', 'required', 'in:low,medium,high'],
                'due_date' => ['nullable', 'date'],
            ];

        $data = $request->validate($rules);

        if (! $isUpdate) {
            $data['completed'] = (bool) ($data['completed'] ?? false);
        }

        $data['priority'] = $data['priority'] ?? 'medium';

        return $data;
    }
}
