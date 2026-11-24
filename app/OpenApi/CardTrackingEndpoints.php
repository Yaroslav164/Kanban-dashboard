<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="CardHistoryEntry", type="object", additionalProperties=false, required={"id","kind","detail","actor_name","created_at"},
 *
 *     @OA\Property(property="id", type="integer", minimum=1),
 *     @OA\Property(property="kind", type="string", enum={"moved","start_date","due_date","assignees","completed","reopened"}),
 *     @OA\Property(property="detail", type="string", nullable=true),
 *     @OA\Property(property="actor_name", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(schema="BoardNotification", type="object", additionalProperties=false, required={"id","kind","board_id","card_id","card_title","read","created_at"},
 *
 *     @OA\Property(property="id", type="integer", minimum=1),
 *     @OA\Property(property="kind", type="string", enum={"assigned","mentioned","due_soon"}),
 *     @OA\Property(property="board_id", type="integer", minimum=1),
 *     @OA\Property(property="card_id", type="integer", minimum=1),
 *     @OA\Property(property="card_title", type="string"),
 *     @OA\Property(property="read", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Get(path="/api/cards/{card}/history", operationId="listCardHistory", tags={"Cards"}, summary="List the 100 latest changes to a card",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *
 *     @OA\Response(response="200", description="Card history", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", type="array", maxItems=100, @OA\Items(ref="#/components/schemas/CardHistoryEntry")))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404")
 * )
 *
 * @OA\Get(path="/api/me/notifications", operationId="listMyNotifications", tags={"Notifications"}, summary="List inbox notifications with unread entries first",
 *     description="Returns up to 100 private notifications for cards still accessible to the signed-in user. Deadline reminders cover today and tomorrow (application time zone).",
 *
 *     @OA\Response(response="200", description="Notifications", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", type="array", maxItems=100, @OA\Items(ref="#/components/schemas/BoardNotification")))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401")
 * )
 *
 * @OA\Post(path="/api/me/notifications/{notification}/read", operationId="readMyNotification", tags={"Notifications"}, summary="Mark a notification as read",
 *
 *     @OA\Parameter(name="notification", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\Response(response="204", description="Notification marked as read"),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419")
 * )
 */
final class CardTrackingEndpoints {}
