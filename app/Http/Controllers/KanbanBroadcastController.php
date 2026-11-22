<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

final class KanbanBroadcastController extends KanbanController
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->input($request, [
            'socket_id' => 'required|string|regex:/\A[0-9]+\.[0-9]+\z/',
            'channel_name' => 'required|string|regex:/\Aprivate-users\.[0-9]+\z/',
        ]);

        require base_path('routes/channels.php');

        return response()->json(Broadcast::auth($request));
    }
}
