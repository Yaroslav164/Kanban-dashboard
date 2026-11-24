<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\PathItem(
 *     path="/api/columns/{column}/cards",
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/column"
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/columns/{column}/cards",
 *     operationId="listCards",
 *     tags={"Cards"},
 *     summary="List cards",
 *
 *     @OA\Response(
 *         response="200",
 *         description="Collection ordered by position then id ascending",
 *
 *         @OA\MediaType(
 *             mediaType="application/json",
 *
 *             @OA\Schema(
 *                 type="object",
 *                 additionalProperties=false,
 *
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *
 *                     @OA\Items(
 *                         ref="#/components/schemas/Card"
 *                     )
 *                 ),
 *                 required={"data"}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="404",
 *         ref="#/components/responses/Error404"
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/columns/{column}/cards",
 *     operationId="createCard",
 *     tags={"Cards"},
 *     summary="Create a card",
 *
 *     @OA\Response(
 *         response="201",
 *         description="Created",
 *
 *         @OA\MediaType(
 *             mediaType="application/json",
 *
 *             @OA\Schema(
 *                 type="object",
 *                 additionalProperties=false,
 *
 *                 @OA\Property(
 *                     property="data",
 *                     ref="#/components/schemas/Card"
 *                 ),
 *                 required={"data"}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *     @OA\Response(
 *         response="403",
 *         ref="#/components/responses/Error403"
 *     ),
 *     @OA\Response(
 *         response="404",
 *         ref="#/components/responses/Error404"
 *     ),
 *     @OA\Response(
 *         response="422",
 *         ref="#/components/responses/Error422"
 *     ),
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
 *                 ref="#/components/schemas/CreateCard"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/columns/{column}/cards/{card}",
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/column"
 *     ),
 *     @OA\Parameter(
 *         ref="#/components/parameters/card"
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/columns/{column}/cards/{card}",
 *     operationId="getCard",
 *     tags={"Cards"},
 *     summary="Read a card",
 *
 *     @OA\Response(
 *         response="200",
 *         description="Requested resource",
 *
 *         @OA\MediaType(
 *             mediaType="application/json",
 *
 *             @OA\Schema(
 *                 type="object",
 *                 additionalProperties=false,
 *
 *                 @OA\Property(
 *                     property="data",
 *                     ref="#/components/schemas/Card"
 *                 ),
 *                 required={"data"}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="404",
 *         ref="#/components/responses/Error404"
 *     )
 * )
 *
 * @OA\Patch(
 *     path="/api/columns/{column}/cards/{card}",
 *     operationId="updateCard",
 *     tags={"Cards"},
 *     summary="Update a card",
 *
 *     @OA\Response(
 *         response="409",
 *         description="An original field value differs from the current card",
 *
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     ),
 *
 *     @OA\Response(
 *         response="200",
 *         description="Updated",
 *
 *         @OA\MediaType(
 *             mediaType="application/json",
 *
 *             @OA\Schema(
 *                 type="object",
 *                 additionalProperties=false,
 *
 *                 @OA\Property(
 *                     property="data",
 *                     ref="#/components/schemas/Card"
 *                 ),
 *                 required={"data"}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *     @OA\Response(
 *         response="403",
 *         ref="#/components/responses/Error403"
 *     ),
 *     @OA\Response(
 *         response="404",
 *         ref="#/components/responses/Error404"
 *     ),
 *     @OA\Response(
 *         response="422",
 *         ref="#/components/responses/Error422"
 *     ),
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
 *                 ref="#/components/schemas/PatchCard"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/columns/{column}/cards/{card}",
 *     operationId="deleteCard",
 *     tags={"Cards"},
 *     summary="Soft-delete a card",
 *
 *     @OA\Response(
 *         response="204",
 *         description="Resource and any descendants soft-deleted"
 *     ),
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *     @OA\Response(
 *         response="403",
 *         ref="#/components/responses/Error403"
 *     ),
 *     @OA\Response(
 *         response="404",
 *         ref="#/components/responses/Error404"
 *     ),
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/CsrfToken"
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/cards/{card}/move",
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/card"
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/cards/{card}/move",
 *     operationId="moveCard",
 *     tags={"Cards"},
 *     summary="Reorder or move a card",
 *
 *     @OA\Response(
 *         response="200",
 *         description="Moved card",
 *
 *         @OA\MediaType(
 *             mediaType="application/json",
 *
 *             @OA\Schema(
 *                 type="object",
 *                 additionalProperties=false,
 *
 *                 @OA\Property(
 *                     property="data",
 *                     ref="#/components/schemas/Card"
 *                 ),
 *                 required={"data"}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *     @OA\Response(
 *         response="403",
 *         ref="#/components/responses/Error403"
 *     ),
 *     @OA\Response(
 *         response="404",
 *         ref="#/components/responses/Error404"
 *     ),
 *     @OA\Response(
 *         response="409",
 *         ref="#/components/responses/Error409"
 *     ),
 *     @OA\Response(
 *         response="422",
 *         ref="#/components/responses/Error422"
 *     ),
 *     description="Both neighbor keys are required. Evaluate neighbors in the target column after excluding the moving card. Null previous means first, null next means last, both null require an empty target. Non-null neighbors must be distinct and differ from the moving card (422). Unknown or inaccessible card or target column returns 404. A different accessible board or a stale destination gap returns 409. Repeating the same move succeeds while its neighbors still match. The server assigns position.",
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
 *                 ref="#/components/schemas/MoveCard"
 *             )
 *         )
 *     )
 * )
 */
final class CardsEndpoints {}
