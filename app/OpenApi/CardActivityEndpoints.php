<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="CardLabel", type="object", additionalProperties=false, required={"name","color"}, @OA\Property(property="name", type="string", minLength=1, maxLength=50), @OA\Property(property="color", type="string", pattern="^#[0-9a-fA-F]{6}$"))
 * @OA\Schema(schema="CardChecklistItem", type="object", additionalProperties=false, required={"id","title","completed"}, @OA\Property(property="id", type="string", format="uuid"), @OA\Property(property="title", type="string", minLength=1, maxLength=255), @OA\Property(property="completed", type="boolean"))
 * @OA\Schema(schema="CardChecklist", type="object", additionalProperties=false, required={"id","title","items"}, @OA\Property(property="id", type="string", format="uuid"), @OA\Property(property="title", type="string", minLength=1, maxLength=255), @OA\Property(property="items", type="array", maxItems=100, @OA\Items(ref="#/components/schemas/CardChecklistItem")))
 * @OA\Schema(schema="CardCommentAuthor", type="object", additionalProperties=false, nullable=true, required={"id","name"}, @OA\Property(property="id", type="integer", format="int64", minimum=1), @OA\Property(property="name", type="string"))
 * @OA\Schema(schema="CardComment", type="object", additionalProperties=false, required={"id","body","author","created_at"}, @OA\Property(property="id", type="integer", format="int64", minimum=1), @OA\Property(property="body", type="string", minLength=1, maxLength=10000), @OA\Property(property="author", ref="#/components/schemas/CardCommentAuthor"), @OA\Property(property="created_at", type="string", format="date-time"))
 * @OA\Schema(schema="CardAttachment", type="object", additionalProperties=false, required={"id","name","mime_type","size","created_at"}, @OA\Property(property="id", type="integer", format="int64", minimum=1), @OA\Property(property="name", type="string"), @OA\Property(property="mime_type", type="string"), @OA\Property(property="size", type="integer", minimum=1, maximum=10485760), @OA\Property(property="created_at", type="string", format="date-time"))
 * @OA\Schema(schema="CardActivity", type="object", additionalProperties=false, required={"comments","attachments"}, @OA\Property(property="comments", type="array", @OA\Items(ref="#/components/schemas/CardComment")), @OA\Property(property="attachments", type="array", @OA\Items(ref="#/components/schemas/CardAttachment")))
 *
 * @OA\Get(
 *     path="/api/cards/{card}/activity",
 *     operationId="getCardActivity",
 *     tags={"Cards"},
 *     summary="List card comments and attachments",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *
 *     @OA\Response(response="200", description="Card activity data", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/CardActivity"))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404")
 * )
 *
 * @OA\Post(
 *     path="/api/cards/{card}/comments",
 *     operationId="createCardComment",
 *     tags={"Cards"},
 *     summary="Add a comment",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(type="object", additionalProperties=false, required={"body"}, @OA\Property(property="body", type="string", minLength=1, maxLength=10000))),
 *
 *     @OA\Response(response="201", description="Card activity data", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/CardComment"))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419"),
 *     @OA\Response(response="422", ref="#/components/responses/Error422")
 * )
 *
 * @OA\Delete(
 *     path="/api/cards/{card}/comments/{comment}",
 *     operationId="deleteCardComment",
 *     tags={"Cards"},
 *     summary="Delete your own comment",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *     @OA\Parameter(name="comment", in="path", required=true, @OA\Schema(type="integer", format="int64", minimum=1)),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\Response(response="204", description="Removed"),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419")
 * )
 *
 * @OA\Post(
 *     path="/api/cards/{card}/attachments",
 *     operationId="uploadCardAttachment",
 *     tags={"Cards"},
 *     summary="Attach a file",
 *     description="Up to 10 MiB per file, 20 files per card and 50 MiB total per card.",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"}, @OA\Property(property="file", type="string", format="binary")))),
 *
 *     @OA\Response(response="201", description="Card activity data", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/CardAttachment"))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419"),
 *     @OA\Response(response="422", ref="#/components/responses/Error422")
 * )
 *
 * @OA\Get(
 *     path="/api/cards/{card}/attachments/{attachment}",
 *     operationId="downloadCardAttachment",
 *     tags={"Cards"},
 *     summary="Download a private attachment",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *     @OA\Parameter(name="attachment", in="path", required=true, @OA\Schema(type="integer", format="int64", minimum=1)),
 *     @OA\Parameter(name="inline", in="query", description="Render a supported image inline", @OA\Schema(type="boolean")),
 *
 *     @OA\Response(response="200", description="File bytes", @OA\MediaType(mediaType="application/octet-stream", @OA\Schema(type="string", format="binary")), @OA\MediaType(mediaType="image/*", @OA\Schema(type="string", format="binary"))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404")
 * )
 *
 * @OA\Delete(
 *     path="/api/cards/{card}/attachments/{attachment}",
 *     operationId="deleteCardAttachment",
 *     tags={"Cards"},
 *     summary="Remove an attachment",
 *
 *     @OA\Parameter(ref="#/components/parameters/card"),
 *     @OA\Parameter(name="attachment", in="path", required=true, @OA\Schema(type="integer", format="int64", minimum=1)),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\Response(response="204", description="Removed"),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419")
 * )
 */
final class CardActivityEndpoints {}
