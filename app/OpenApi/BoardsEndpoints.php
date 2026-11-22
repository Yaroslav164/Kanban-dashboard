<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/boards",
 *     operationId="listBoards",
 *     tags={"Boards"},
 *     summary="List boards",
 *
 *     @OA\Response(
 *         response="200",
 *         description="Collection ordered by id ascending",
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
 *                         ref="#/components/schemas/Board"
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
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/boards",
 *     operationId="createBoard",
 *     tags={"Boards"},
 *     summary="Create a board",
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
 *                     ref="#/components/schemas/Board"
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
 *                 ref="#/components/schemas/CreateBoard"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/boards/{board}",
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/board"
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/boards/{board}",
 *     operationId="getBoard",
 *     tags={"Boards"},
 *     summary="Read a board",
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
 *                     ref="#/components/schemas/BoardDetail"
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
 *     path="/api/boards/{board}",
 *     operationId="updateBoard",
 *     tags={"Boards"},
 *     summary="Update a board",
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
 *                     ref="#/components/schemas/Board"
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
 *                 ref="#/components/schemas/PatchBoard"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/boards/{board}",
 *     operationId="deleteBoard",
 *     tags={"Boards"},
 *     summary="Soft-delete a board",
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
 */
final class BoardsEndpoints {}
