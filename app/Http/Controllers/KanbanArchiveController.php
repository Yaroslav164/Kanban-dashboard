<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CardArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class KanbanArchiveController extends KanbanController
{
    public function __construct(private CardArchiveService $archive) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->archive->all($request->user()->id, $this->id($request, 'board')));
    }

    public function archive(Request $request): Response
    {
        $this->input($request, []);
        $this->archive->archive($request->user()->id, $this->id($request, 'card'));

        return response()->noContent();
    }

    public function restore(Request $request): Response
    {
        $input = $this->input($request, ['column_id' => 'sometimes|required|integer:strict|min:1']);
        $this->archive->restore(
            $request->user()->id,
            $this->id($request, 'card'),
            $input['column_id'] ?? null,
        );

        return response()->noContent();
    }
}
