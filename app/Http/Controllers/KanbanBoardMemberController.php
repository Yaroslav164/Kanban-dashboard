<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BoardMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class KanbanBoardMemberController extends KanbanController
{
    public function __construct(private BoardMemberService $members) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->members->all($request->user()->id, $this->id($request, 'board')));
    }

    public function store(Request $request): JsonResponse
    {
        $input = $this->input($request, ['email' => 'required|string|email|max:255', 'role' => 'required|string|max:40']);

        return $this->data($this->members->add(
            $request->user()->id, $this->id($request, 'board'), $input['email'], $input['role'],
        ), 201);
    }

    public function update(Request $request): JsonResponse
    {
        $input = $this->input($request, ['role' => 'required|string|max:40']);

        return $this->data($this->members->changeRole(
            $request->user()->id, $this->id($request, 'board'), $this->id($request, 'member'), $input['role'],
        ));
    }

    public function destroy(Request $request): Response
    {
        $this->members->remove($request->user()->id, $this->id($request, 'board'), $this->id($request, 'member'));

        return response()->noContent();
    }
}
