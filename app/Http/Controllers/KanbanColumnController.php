<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ColumnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class KanbanColumnController extends KanbanController
{
    public function __construct(private ColumnService $columns) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->columns->all($request->user()->id, $this->id($request, 'board')));
    }

    public function store(Request $request): JsonResponse
    {
        $input = $this->input($request, ['title' => 'required|string|max:255']);

        return $this->data($this->columns->create($request->user()->id, $this->id($request, 'board'), $input['title']), 201);
    }

    public function show(Request $request): JsonResponse
    {
        return $this->data($this->columns->find($request->user()->id, $this->id($request, 'board'), $this->id($request, 'column')));
    }

    public function update(Request $request): JsonResponse
    {
        $input = $this->input($request, ['title' => 'required|string|max:255']);

        return $this->data($this->columns->rename(
            $request->user()->id, $this->id($request, 'board'), $this->id($request, 'column'), $input['title'],
        ));
    }

    public function destroy(Request $request): Response
    {
        $this->columns->delete($request->user()->id, $this->id($request, 'board'), $this->id($request, 'column'));

        return response()->noContent();
    }
}
