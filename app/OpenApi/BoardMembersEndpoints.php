<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="BoardMember",
 *     type="object",
 *     additionalProperties=false,
 *     required={"id", "name", "email", "role"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", minimum=1),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="role", ref="#/components/schemas/BoardRoleKey")
 * )
 *
 * @OA\Get(
 *     path="/api/boards/{board}/members",
 *     operationId="listBoardMembers",
 *     tags={"Board members"},
 *     summary="List board members",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BoardMember")))),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404")
 * )
 *
 * @OA\Post(
 *     path="/api/boards/{board}/members",
 *     operationId="addBoardMember",
 *     tags={"Board members"},
 *     summary="Add a registered user to the board",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(type="object", additionalProperties=false, required={"email", "role"}, @OA\Property(property="email", type="string", format="email", maxLength=255), @OA\Property(property="role", type="string", maxLength=40, pattern="^(editor|viewer|custom-[1-9][0-9]*)$"))),
 *
 *     @OA\Response(response=201, description="Member added", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/BoardMember"))),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=403, ref="#/components/responses/Error403"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404"),
 *     @OA\Response(response=419, ref="#/components/responses/Error419"),
 *     @OA\Response(response=422, ref="#/components/responses/Error422")
 * )
 *
 * @OA\Patch(
 *     path="/api/boards/{board}/members/{member}",
 *     operationId="updateBoardMember",
 *     tags={"Board members"},
 *     summary="Change a board member role",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *     @OA\Parameter(name="member", in="path", required=true, description="Member user ID", @OA\Schema(type="integer", format="int64", minimum=1)),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(type="object", additionalProperties=false, required={"role"}, @OA\Property(property="role", type="string", maxLength=40, pattern="^(editor|viewer|custom-[1-9][0-9]*)$"))),
 *
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/BoardMember"))),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=403, ref="#/components/responses/Error403"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404"),
 *     @OA\Response(response=419, ref="#/components/responses/Error419"),
 *     @OA\Response(response=422, ref="#/components/responses/Error422")
 * )
 *
 * @OA\Delete(
 *     path="/api/boards/{board}/members/{member}",
 *     operationId="deleteBoardMember",
 *     tags={"Board members"},
 *     summary="Remove a board member",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *     @OA\Parameter(name="member", in="path", required=true, description="Member user ID", @OA\Schema(type="integer", format="int64", minimum=1)),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\Response(response=204, description="Member removed"),
 *     @OA\Response(response=401, ref="#/components/responses/Error401"),
 *     @OA\Response(response=403, ref="#/components/responses/Error403"),
 *     @OA\Response(response=404, ref="#/components/responses/Error404"),
 *     @OA\Response(response=419, ref="#/components/responses/Error419"),
 *     @OA\Response(response=422, ref="#/components/responses/Error422")
 * )
 */
final class BoardMembersEndpoints {}
