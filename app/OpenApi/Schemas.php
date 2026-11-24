<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         maxLength=255
 *     ),
 *     required={"id", "name", "email"}
 * )
 *
 * @OA\Schema(
 *     schema="Login",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         description="At most 72 UTF-8 bytes, without NUL characters.",
 *         writeOnly=true
 *     ),
 *     required={"email", "password"}
 * )
 *
 * @OA\Schema(
 *     schema="Registration",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         description="At most 72 UTF-8 bytes, without NUL characters.",
 *         writeOnly=true,
 *         minLength=8
 *     ),
 *     @OA\Property(
 *         property="password_confirmation",
 *         type="string",
 *         format="password",
 *         writeOnly=true
 *     ),
 *     required={"name", "email", "password", "password_confirmation"}
 * )
 *
 * @OA\Schema(
 *     schema="Board",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(property="role", ref="#/components/schemas/BoardRoleKey"),
 *     @OA\Property(property="permissions", ref="#/components/schemas/Permissions"),
 *     required={"id", "title", "created_at", "updated_at", "role", "permissions"}
 * )
 *
 * @OA\Schema(
 *     schema="Column",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="board_id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="position",
 *         type="integer",
 *         format="int32",
 *         minimum=1
 *     ),
 *     required={"id", "title", "created_at", "updated_at", "board_id", "position"}
 * )
 *
 * @OA\Schema(
 *     schema="Card",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="column_id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         maxLength=10000
 *     ),
 *     @OA\Property(
 *         property="position",
 *         type="number",
 *         format="double"
 *     ),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="due_date", type="string", format="date", nullable=true, description="On or after start_date"),
 *     @OA\Property(property="completed", type="boolean"),
 *     @OA\Property(property="labels", type="array", maxItems=50, @OA\Items(ref="#/components/schemas/CardLabel")),
 *     @OA\Property(property="assignee_ids", type="array", uniqueItems=true, @OA\Items(type="integer", format="int64", minimum=1)),
 *     @OA\Property(property="checklists", type="array", maxItems=20, @OA\Items(ref="#/components/schemas/CardChecklist")),
 *     required={"id", "title", "created_at", "updated_at", "column_id", "description", "position", "start_date", "due_date", "completed", "labels", "assignee_ids", "checklists"}
 * )
 *
 * @OA\Schema(
 *     schema="BoardDetail",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="columns",
 *         type="array",
 *
 *         @OA\Items(
 *             ref="#/components/schemas/ColumnDetail"
 *         ),
 *         description="Active columns in position, id order."
 *     ),
 *
 *     @OA\Property(property="role", ref="#/components/schemas/BoardRoleKey"),
 *     @OA\Property(property="permissions", ref="#/components/schemas/Permissions"),
 *     required={"id", "title", "created_at", "updated_at", "columns", "role", "permissions"}
 * )
 *
 * @OA\Schema(
 *     schema="ColumnDetail",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="board_id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="position",
 *         type="integer",
 *         format="int32",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="cards",
 *         type="array",
 *
 *         @OA\Items(
 *             ref="#/components/schemas/Card"
 *         ),
 *         description="Active cards in position, id order."
 *     ),
 *     required={"id", "title", "created_at", "updated_at", "board_id", "position", "cards"}
 * )
 *
 * @OA\Schema(
 *     schema="MoveCard",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="column_id",
 *         type="integer",
 *         format="int64",
 *         minimum=1
 *     ),
 *     @OA\Property(
 *         property="previous_card_id",
 *         type="integer",
 *         format="int64",
 *         minimum=1,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="next_card_id",
 *         type="integer",
 *         format="int64",
 *         minimum=1,
 *         nullable=true
 *     ),
 *     required={"column_id", "previous_card_id", "next_card_id"}
 * )
 *
 * @OA\Schema(
 *     schema="CreateBoard",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     required={"title"}
 * )
 *
 * @OA\Schema(
 *     schema="PatchBoard",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     minProperties=1
 * )
 *
 * @OA\Schema(
 *     schema="CreateColumn",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     required={"title"}
 * )
 *
 * @OA\Schema(
 *     schema="PatchColumn",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     minProperties=1
 * )
 *
 * @OA\Schema(
 *     schema="CreateCard",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         maxLength=10000
 *     ),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="due_date", type="string", format="date", nullable=true, description="On or after start_date"),
 *     @OA\Property(property="completed", type="boolean"),
 *     @OA\Property(property="labels", type="array", maxItems=50, @OA\Items(ref="#/components/schemas/CardLabel")),
 *     @OA\Property(property="assignee_ids", type="array", uniqueItems=true, @OA\Items(type="integer", format="int64", minimum=1)),
 *     @OA\Property(property="checklists", type="array", maxItems=20, @OA\Items(ref="#/components/schemas/CardChecklist")),
 *     required={"title"}
 * )
 *
 * @OA\Schema(
 *     schema="PatchCard",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         minLength=1,
 *         maxLength=255
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         maxLength=10000
 *     ),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="due_date", type="string", format="date", nullable=true, description="On or after start_date"),
 *     @OA\Property(property="completed", type="boolean"),
 *     @OA\Property(property="labels", type="array", maxItems=50, @OA\Items(ref="#/components/schemas/CardLabel")),
 *     @OA\Property(property="assignee_ids", type="array", uniqueItems=true, @OA\Items(type="integer", format="int64", minimum=1)),
 *     @OA\Property(property="checklists", type="array", maxItems=20, @OA\Items(ref="#/components/schemas/CardChecklist")),
 *     @OA\Property(
 *         property="base",
 *         type="object",
 *         additionalProperties=false,
 *         minProperties=1,
 *         description="Original values of exactly the fields included in this update. A mismatch returns HTTP 409.",
 *         @OA\Property(property="title", type="string", minLength=1, maxLength=255),
 *         @OA\Property(property="description", type="string", nullable=true, maxLength=10000),
 *         @OA\Property(property="start_date", type="string", format="date", nullable=true),
 *         @OA\Property(property="due_date", type="string", format="date", nullable=true),
 *         @OA\Property(property="completed", type="boolean"),
 *         @OA\Property(property="labels", type="array", maxItems=50, @OA\Items(ref="#/components/schemas/CardLabel")),
 *         @OA\Property(property="assignee_ids", type="array", uniqueItems=true, @OA\Items(type="integer", format="int64", minimum=1)),
 *         @OA\Property(property="checklists", type="array", maxItems=20, @OA\Items(ref="#/components/schemas/CardChecklist"))
 *     ),
 *     minProperties=1
 * )
 *
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="message",
 *         type="string"
 *     ),
 *     required={"message"}
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     additionalProperties=false,
 *
 *     @OA\Property(
 *         property="message",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *
 *         @OA\AdditionalProperties(
 *             type="array",
 *
 *             @OA\Items(
 *                 type="string"
 *             )
 *         )
 *     ),
 *     required={"message", "errors"}
 * )
 */
final class Schemas {}
