<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\PathItem(
 *     path="/api/boards/{board}/columns",
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/board"
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/boards/{board}/columns",
 *     operationId="listColumns",
 *     tags={"Columns"},
 *     summary="List columns",
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
 *                         ref="#/components/schemas/Column"
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
 *     path="/api/boards/{board}/columns",
 *     operationId="createColumn",
 *     tags={"Columns"},
 *     summary="Create a column",
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
 *                     ref="#/components/schemas/Column"
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
 *                 ref="#/components/schemas/CreateColumn"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\PathItem(
 *     path="/api/boards/{board}/columns/{column}",
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/board"
 *     ),
 *     @OA\Parameter(
 *         ref="#/components/parameters/column"
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/boards/{board}/columns/{column}",
 *     operationId="getColumn",
 *     tags={"Columns"},
 *     summary="Read a column",
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
 *                     ref="#/components/schemas/Column"
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
 *     path="/api/boards/{board}/columns/{column}",
 *     operationId="updateColumn",
 *     tags={"Columns"},
 *     summary="Update a column",
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
 *                     ref="#/components/schemas/Column"
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
 *                 ref="#/components/schemas/PatchColumn"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/boards/{board}/columns/{column}",
 *     operationId="deleteColumn",
 *     tags={"Columns"},
 *     summary="Soft-delete a column",
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
final class ColumnsEndpoints {}
