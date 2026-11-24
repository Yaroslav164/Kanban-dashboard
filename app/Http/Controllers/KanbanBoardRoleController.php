<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BoardRole;
use App\Services\BoardRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

final class KanbanBoardRoleController extends KanbanController
{
    public function __construct(private BoardRoleService $roles) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->roles->all($request->user()->id, $this->id($request, 'board')));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->save($request, $this->id($request, 'role'));
    }

    private function save(Request $request, ?int $id): JsonResponse
    {
        $input = $this->input($request, [
            'name' => 'required|string|max:80',
            'permissions' => ['present', 'array', 'list', 'max:'.count(BoardRole::PERMISSIONS)],
            'permissions.*' => ['string', 'distinct:strict', Rule::in(BoardRole::PERMISSIONS)],
        ]);

        return $this->data($this->roles->save($request->user()->id, $this->id($request, 'board'), $id, $input), $id === null ? 201 : 200);
    }

    public function destroy(Request $request): Response
    {
        $this->roles->remove($request->user()->id, $this->id($request, 'board'), $this->id($request, 'role'));

        return response()->noContent();
    }
}
