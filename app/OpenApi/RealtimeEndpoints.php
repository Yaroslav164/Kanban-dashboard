<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/api/broadcasting/auth",
 *     operationId="authorizePrivateChannel",
 *     tags={"Realtime"},
 *     summary="Authorize a Laravel Reverb private user channel",
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/CsrfToken"
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\MediaType(
 *             mediaType="application/json",
 *
 *             @OA\Schema(
 *                 type="object",
 *                 required={"socket_id", "channel_name"},
 *                 additionalProperties=false,
 *
 *                 @OA\Property(
 *                     property="socket_id",
 *                     type="string",
 *                     pattern="^\d+\.\d+$"
 *                 ),
 *                 @OA\Property(
 *                     property="channel_name",
 *                     type="string",
 *                     pattern="^private-users\.\d+$"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="200",
 *         description="Pusher protocol channel signature",
 *
 *         @OA\MediaType(
 *             mediaType="application/json",
 *
 *             @OA\Schema(
 *                 type="object",
 *                 required={"auth"},
 *                 additionalProperties=false,
 *
 *                 @OA\Property(
 *                     property="auth",
 *                     type="string"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="403",
 *         ref="#/components/responses/Error403"
 *     ),
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *     @OA\Response(
 *         response="422",
 *         ref="#/components/responses/Error422"
 *     )
 * )
 */
final class RealtimeEndpoints {}
