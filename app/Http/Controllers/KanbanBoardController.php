<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class KanbanBoardController extends KanbanController
{
    public function __construct(private BoardService $boards) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->boards->all($request->user()->id));
    }

    public function store(Request $request): JsonResponse
    {
        $input = $this->input($request, ['title' => 'required|string|max:255']);

        return $this->data($this->boards->create($request->user()->id, $input['title']), 201);
    }

    public function assignedTasks(Request $request): JsonResponse
    {
        return $this->data($this->boards->assignedTasks($request->user()->id));
    }

    public function show(Request $request): JsonResponse
    {
        return $this->data($this->boards->find($request->user()->id, $this->id($request, 'board')));
    }

    public function update(Request $request): JsonResponse
    {
        $input = $this->input($request, ['title' => 'required|string|max:255']);

        return $this->data($this->boards->rename($request->user()->id, $this->id($request, 'board'), $input['title']));
    }

    public function destroy(Request $request): Response
    {
        $this->boards->delete($request->user()->id, $this->id($request, 'board'));

        return response()->noContent();
    }
}
