<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CardTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KanbanCardHistoryController extends KanbanController
{
    public function __construct(private CardTrackingService $tracking) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->tracking->history($request->user()->id, $this->id($request, 'card')));
    }
}
