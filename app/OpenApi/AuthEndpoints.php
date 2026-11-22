<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/sanctum/csrf-cookie",
 *     operationId="csrfCookie",
 *     tags={"Auth"},
 *     summary="Initialize CSRF cookies",
 *
 *     @OA\Response(
 *         response="204",
 *         description="Cookies initialized",
 *
 *         @OA\Header(
 *             header="Set-Cookie",
 *             description="Initializes XSRF-TOKEN and the browser session. Preserve all Set-Cookie headers.",
 *
 *             @OA\Schema(
 *                 type="string"
 *             )
 *         )
 *     ),
 *     security={}
 * )
 *
 * @OA\Post(
 *     path="/api/auth/register",
 *     operationId="authRegister",
 *     tags={"Auth"},
 *     summary="Register and start a session",
 *
 *     @OA\Response(
 *         response="201",
 *         description="Registered",
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
 *                     ref="#/components/schemas/User"
 *                 ),
 *                 required={"data"}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *     @OA\Response(
 *         response="422",
 *         ref="#/components/responses/Error422"
 *     ),
 *     security={},
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
 *                 ref="#/components/schemas/Registration"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/login",
 *     operationId="authLogin",
 *     tags={"Auth"},
 *     summary="Start a session",
 *
 *     @OA\Response(
 *         response="200",
 *         description="Current user",
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
 *                     ref="#/components/schemas/User"
 *                 ),
 *                 required={"data"}
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *     @OA\Response(
 *         response="422",
 *         ref="#/components/responses/Error422"
 *     ),
 *     security={},
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
 *                 ref="#/components/schemas/Login"
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/auth/logout",
 *     operationId="authLogout",
 *     tags={"Auth"},
 *     summary="End the current session",
 *
 *     @OA\Response(
 *         response="204",
 *         description="Logged out"
 *     ),
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="419",
 *         ref="#/components/responses/Error419"
 *     ),
 *
 *     @OA\Parameter(
 *         ref="#/components/parameters/CsrfToken"
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/auth/me",
 *     operationId="authMe",
 *     tags={"Auth"},
 *     summary="Read current user",
 *
 *     @OA\Response(
 *         response="200",
 *         description="Current user",
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
 *                     ref="#/components/schemas/User"
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
 * @OA\Get(
 *     path="/auth/github/redirect",
 *     operationId="startGithubOAuth",
 *     tags={"Auth"},
 *     summary="Start GitHub sign-in or link the signed-in account",
 *     description="Socialite stores a single-use state, intent and initiating user in the session. Linking requires intent=link and the same authenticated user at callback.",
 *     security={},
 *
 *     @OA\Parameter(
 *         name="intent",
 *         in="query",
 *         required=false,
 *
 *         @OA\Schema(
 *             type="string",
 *             enum={"login", "link"},
 *             default="login"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="302",
 *         description="Redirect to the GitHub authorization page requesting read:user and user:email.",
 *
 *         @OA\Header(
 *             header="Location",
 *
 *             @OA\Schema(
 *                 type="string"
 *             ),
 *             description="Navigation destination."
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="401",
 *         ref="#/components/responses/Error401"
 *     ),
 *     @OA\Response(
 *         response="422",
 *         ref="#/components/responses/Error422"
 *     )
 * )
 *
 * @OA\Get(
 *     path="/auth/github/callback",
 *     operationId="finishGithubOAuth",
 *     tags={"Auth"},
 *     summary="Complete a state-validated GitHub sign-in or link",
 *     description="Known provider IDs sign in to the linked account. A new account requires a verified primary email. Existing emails never cause implicit account linking. Explicit linking requires the initiating session and an unclaimed provider ID. Rotate the session on sign-in. Errors redirect with one of invalid_state, denied, email_unverified, account_exists, identity_in_use or provider_unavailable. All redirect locations are fixed SPA paths.",
 *     security={},
 *
 *     @OA\Parameter(
 *         name="code",
 *         in="query",
 *         required=false,
 *
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *
 *     @OA\Parameter(
 *         name="state",
 *         in="query",
 *         required=false,
 *
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *
 *     @OA\Parameter(
 *         name="error",
 *         in="query",
 *         required=false,
 *
 *         @OA\Schema(
 *             type="string"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response="302",
 *         description="Successful login or link redirects to /boards. Errors redirect to /login?oauth={error} for sign-in, /settings/account?oauth={error} for a verified link attempt. If state cannot be validated use /login?oauth=invalid_state.",
 *
 *         @OA\Header(
 *             header="Location",
 *
 *             @OA\Schema(
 *                 type="string"
 *             ),
 *             description="Navigation destination."
 *         )
 *     )
 * )
 */
final class AuthEndpoints {}
