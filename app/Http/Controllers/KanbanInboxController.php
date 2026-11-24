<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\InboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class KanbanInboxController extends KanbanController
{
    public function __construct(private InboxService $inbox) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->inbox->all($request->user()->id));
    }

    public function read(Request $request): Response
    {
        $this->inbox->read($request->user()->id, $this->id($request, 'notification'));

        return response()->noContent();
    }
}
