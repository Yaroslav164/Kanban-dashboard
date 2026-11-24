<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="AssignedTask", type="object", additionalProperties=false, required={"id","title","board_id","board_title","column_id","column_title","due_date"},
 *
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="board_id", type="integer", format="int64"),
 *     @OA\Property(property="board_title", type="string"),
 *     @OA\Property(property="column_id", type="integer", format="int64"),
 *     @OA\Property(property="column_title", type="string"),
 *     @OA\Property(property="due_date", type="string", format="date", nullable=true)
 * )
 *
 * @OA\Get(path="/api/me/tasks", operationId="listMyTasks", tags={"Cards"}, summary="List the current user's incomplete assigned cards across accessible boards",
 *
 *     @OA\Response(response="200", description="Active tasks ordered by due date, then id", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AssignedTask")))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401")
 * )
 */
final class MyTasksEndpoint {}
