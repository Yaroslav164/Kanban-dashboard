<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Parameter(
 *     parameter="board",
 *     name="board",
 *     in="path",
 *     required=true,
 *
 *     @OA\Schema(
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="column",
 *     name="column",
 *     in="path",
 *     required=true,
 *
 *     @OA\Schema(
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="card",
 *     name="card",
 *     in="path",
 *     required=true,
 *
 *     @OA\Schema(
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="CsrfToken",
 *     name="X-XSRF-TOKEN",
 *     in="header",
 *     required=true,
 *
 *     @OA\Schema(
 *         type="string"
 *     ),
 *     description="URL-decoded value of the XSRF-TOKEN cookie."
 * )
 */
final class Parameters {}
