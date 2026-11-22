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
 *     description="Resource missing, deleted, outside nested parent, or owned by another user",
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
 *     description="Target gap has changed or move crosses owned boards",
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
 *     description="Invalid request fields or incorrect login credentials",
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
 *     description="Requested private channel is not owned by the current user.",
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
