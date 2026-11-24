<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="ArchivedCard", type="object", additionalProperties=false, required={"id","title","column_id","column_title"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", minimum=1),
 *     @OA\Property(property="title", type="string", minLength=1, maxLength=255),
 *     @OA\Property(property="column_id", type="integer", format="int64", minimum=1),
 *     @OA\Property(property="column_title", type="string", minLength=1, maxLength=255)
 * )
 *
 * @OA\Get(path="/api/boards/{board}/archive", operationId="listArchivedCards", tags={"Cards"}, summary="List archived cards on the board",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *
 *     @OA\Response(response="200", description="Archived cards", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ArchivedCard")))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404")
 * )
 *
 * @OA\Post(path="/api/cards/{card}/archive", operationId="archiveCard", tags={"Cards"}, summary="Archive a card", description="Requires cards.delete.",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\Response(response="204", description="Archived"),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419"),
 *     @OA\Response(response="422", ref="#/components/responses/Error422")
 * )
 *
 * @OA\Post(path="/api/cards/{card}/restore", operationId="restoreCard", tags={"Cards"}, summary="Restore an archived card",
 *     description="Requires cards.delete. Restores to the end of the original column. Choose another active column on the same board if the original column was deleted.",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=false, @OA\JsonContent(type="object", additionalProperties=false,
 *
 *         @OA\Property(property="column_id", type="integer", format="int64", minimum=1))),
 *
 *     @OA\Response(response="204", description="Restored"),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="409", description="The requested destination conflicts with the status of the original column", @OA\JsonContent(ref="#/components/schemas/Error")),
 *     @OA\Response(response="419", ref="#/components/responses/Error419"),
 *     @OA\Response(response="422", ref="#/components/responses/Error422")
 * )
 */
final class CardArchiveEndpoints {}
