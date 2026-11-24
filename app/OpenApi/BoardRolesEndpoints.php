<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="BoardRoleKey", type="string", pattern="^(owner|editor|viewer|custom-[1-9][0-9]*)$")
 * @OA\Schema(schema="Permissions", type="array", maxItems=10, uniqueItems=true,
 *
 *     @OA\Items(type="string", enum={"board.rename","columns.create","columns.edit","columns.delete","cards.create","cards.edit","cards.move","cards.delete","cards.comment","cards.attach"})
 * )
 *
 * @OA\Schema(schema="RoleInput", type="object", additionalProperties=false, required={"name","permissions"},
 *
 *     @OA\Property(property="name", type="string", minLength=1, maxLength=80),
 *     @OA\Property(property="permissions", ref="#/components/schemas/Permissions")
 * )
 *
 * @OA\Schema(schema="CustomRole", type="object", additionalProperties=false, required={"id","key","name","permissions"},
 *
 *     @OA\Property(property="id", type="integer", minimum=1),
 *     @OA\Property(property="key", type="string", pattern="^custom-[1-9][0-9]*$"),
 *     @OA\Property(property="name", type="string", minLength=1, maxLength=80),
 *     @OA\Property(property="permissions", ref="#/components/schemas/Permissions")
 * )
 *
 * @OA\Get(
 *     path="/api/boards/{board}/roles", operationId="listBoardRoles", tags={"Board roles"}, summary="List custom roles",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *
 *     @OA\Response(response=200, description="List custom roles", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CustomRole")))),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404")
 * )
 *
 * @OA\Post(
 *     path="/api/boards/{board}/roles", operationId="createBoardRole", tags={"Board roles"}, summary="Create a custom role",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RoleInput")),
 *
 *     @OA\Response(response=201, description="Create a custom role", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/CustomRole"))),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=403, ref="#/components/responses/Error403"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404"),
 *     @OA\Response(response=419, ref="#/components/responses/Error419"),
 *     @OA\Response(response=422, ref="#/components/responses/Error422")
 * )
 *
 * @OA\Patch(
 *     path="/api/boards/{board}/roles/{role}", operationId="updateBoardRole", tags={"Board roles"}, summary="Change a custom role",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *     @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RoleInput")),
 *
 *     @OA\Response(response=200, description="Change a custom role", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/CustomRole"))),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=403, ref="#/components/responses/Error403"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404"),
 *     @OA\Response(response=419, ref="#/components/responses/Error419"),
 *     @OA\Response(response=422, ref="#/components/responses/Error422")
 * )
 *
 * @OA\Delete(
 *     path="/api/boards/{board}/roles/{role}", operationId="deleteBoardRole", tags={"Board roles"}, summary="Remove an unassigned custom role",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *     @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\Response(response=204, description="Remove an unassigned custom role"),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=403, ref="#/components/responses/Error403"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404"),
 *     @OA\Response(response=419, ref="#/components/responses/Error419"),
 *     @OA\Response(response=422, ref="#/components/responses/Error422")
 * )
 */
final class BoardRolesEndpoints {}
