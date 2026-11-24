<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="CardTemplate", type="object", additionalProperties=false, required={"id","name","title","description"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", minimum=1),
 *     @OA\Property(property="name", type="string", minLength=1, maxLength=255),
 *     @OA\Property(property="title", type="string", minLength=1, maxLength=255),
 *     @OA\Property(property="description", type="string", nullable=true, maxLength=10000)
 * )
 *
 * @OA\Get(path="/api/boards/{board}/card-templates", operationId="listCardTemplates", tags={"Cards"}, summary="List this board's card templates",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *
 *     @OA\Response(response="200", description="Available templates", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CardTemplate")))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404")
 * )
 *
 * @OA\Post(path="/api/boards/{board}/card-templates", operationId="saveCardTemplate", tags={"Cards"}, summary="Save a board card as a reusable template",
 *     description="Requires cards.create. Takes a snapshot of the title, description, labels and checklists.",
 *
 *     @OA\Parameter(ref="#/components/parameters/board"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(type="object", additionalProperties=false, required={"card_id","name"},
 *
 *         @OA\Property(property="card_id", type="integer", format="int64", minimum=1),
 *         @OA\Property(property="name", type="string", minLength=1, maxLength=255))),
 *
 *     @OA\Response(response="201", description="Saved template", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/CardTemplate"))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419"),
 *     @OA\Response(response="422", ref="#/components/responses/Error422")
 * )
 *
 * @OA\Post(path="/api/columns/{column}/cards/from-template", operationId="createCardFromTemplate", tags={"Cards"}, summary="Append a card using a template from the same board",
 *     description="Requires cards.create. Checklist and item identifiers are new and every item starts unchecked.",
 *
 *     @OA\Parameter(ref="#/components/parameters/column"),
 *     @OA\Parameter(ref="#/components/parameters/CsrfToken"),
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(type="object", additionalProperties=false, required={"template_id"}, @OA\Property(property="template_id", type="integer", format="int64", minimum=1))),
 *
 *     @OA\Response(response="201", description="Created card", @OA\JsonContent(type="object", additionalProperties=false, required={"data"}, @OA\Property(property="data", ref="#/components/schemas/Card"))),
 *     @OA\Response(response="401", ref="#/components/responses/Error401"),
 *     @OA\Response(response="403", ref="#/components/responses/Error403"),
 *     @OA\Response(response="404", ref="#/components/responses/Error404"),
 *     @OA\Response(response="419", ref="#/components/responses/Error419"),
 *     @OA\Response(response="422", ref="#/components/responses/Error422")
 * )
 */
final class CardTemplatesEndpoints {}
