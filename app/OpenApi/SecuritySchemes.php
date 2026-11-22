<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\SecurityScheme(
 *     securityScheme="SanctumSession",
 *     type="apiKey",
 *     in="cookie",
 *     name="laravel_session"
 * )
 */
final class SecuritySchemes {}
