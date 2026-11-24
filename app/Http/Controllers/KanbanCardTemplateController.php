<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CardService;
use App\Services\CardTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KanbanCardTemplateController extends KanbanController
{
    public function __construct(private CardTemplateService $templates, private CardService $cards) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->templates->all($request->user()->id, $this->id($request, 'board')));
    }

    public function store(Request $request): JsonResponse
    {
        $input = $this->input($request, [
            'name' => 'required|string|max:255',
            'card_id' => 'required|integer:strict|min:1',
        ]);

        return $this->data($this->templates->create(
            $request->user()->id, $this->id($request, 'board'), $input['name'], $input['card_id'],
        ), 201);
    }

    public function apply(Request $request): JsonResponse
    {
        $input = $this->input($request, [
            'template_id' => 'required|integer:strict|min:1',
        ]);

        return $this->data($this->cards->createFromTemplate(
            $request->user()->id, $this->id($request, 'column'), $input['template_id'],
        ), 201);
    }
}
