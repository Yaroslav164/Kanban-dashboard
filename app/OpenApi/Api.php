<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     security={{"SanctumSession"={}}},
 *     x={"realtime"={"transport"="Laravel Reverb (Pusher protocol)", "channel"="private-users.{userId}", "event"="board.changed", "description"="Broadcast after a successful commit for board, column and card mutations. Invalidate the board list and refresh the affected open board. After subscription or reconnect, refresh list and current board even when no event was received. Remove a deleted open board on 404.", "payload"={"type"="object", "additionalProperties"=false, "required"={"board_id"}, "properties"={"board_id"={"type"="integer", "format"="int64", "minimum"=1}}}}}
 * )
 *
 * @OA\Info(
 *     title="Kanban MVP",
 *     version="0.1.0",
 *     description="First-party SPA on the same origin. Use Accept: application/json, keep cookies, and URL-decode XSRF-TOKEN into X-XSRF-TOKEN for mutations. Timestamps are UTC RFC 3339 strings. Collection endpoints return all active records."
 * )
 *
 * @OA\Server(
 *     url="/"
 * )
 */
final class Api {}
