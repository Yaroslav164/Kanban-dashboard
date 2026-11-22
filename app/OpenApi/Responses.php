<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Response(
 *     response="Error401",
 *     description="Session missing or expired",
 *
 *     @OA\MediaType(
 *         mediaType="application/json",
 *
 *         @OA\Schema(
 *             ref="#/components/schemas/Error"
 *         )
 *     )
 * )
 *
 * @OA\Response(
 *     response="Error404",
 *     description="Resource missing, deleted, outside nested parent, or inaccessible to the current user",
 *
 *     @OA\MediaType(
 *         mediaType="application/json",
 *
 *         @OA\Schema(
 *             ref="#/components/schemas/Error"
 *         )
 *     )
 * )
 *
 * @OA\Response(
 *     response="Error409",
 *     description="Target gap has changed or move crosses boards",
 *
 *     @OA\MediaType(
 *         mediaType="application/json",
 *
 *         @OA\Schema(
 *             ref="#/components/schemas/Error"
 *         )
 *     )
 * )
 *
 * @OA\Response(
 *     response="Error419",
 *     description="CSRF token missing or expired",
 *
 *     @OA\MediaType(
 *         mediaType="application/json",
 *
 *         @OA\Schema(
 *             ref="#/components/schemas/Error"
 *         )
 *     )
 * )
 *
 * @OA\Response(
 *     response="Error422",
 *     description="Invalid request fields, credentials, or requested membership change",
 *
 *     @OA\MediaType(
 *         mediaType="application/json",
 *
 *         @OA\Schema(
 *             ref="#/components/schemas/ValidationError"
 *         )
 *     )
 * )
 *
 * @OA\Response(
 *     response="Error403",
 *     description="The current user lacks permission for this operation.",
 *
 *     @OA\MediaType(
 *         mediaType="application/json",
 *
 *         @OA\Schema(
 *             ref="#/components/schemas/Error"
 *         )
 *     )
 * )
 */
final class Responses {}
