<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CardService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

final class KanbanCardController extends KanbanController
{
    public function __construct(private CardService $cards) {}

    public function index(Request $request): JsonResponse
    {
        return $this->data($this->cards->all($request->user()->id, $this->id($request, 'column')));
    }

    public function store(Request $request): JsonResponse
    {
        $input = $this->input($request, [
            'title' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string|max:10000',
        ] + $this->detailRules());

        return $this->data($this->cards->create($request->user()->id, $this->id($request, 'column'), $input), 201);
    }

    public function show(Request $request): JsonResponse
    {
        return $this->data($this->cards->find($request->user()->id, $this->id($request, 'column'), $this->id($request, 'card')));
    }

    public function update(Request $request): JsonResponse
    {
        $rules = [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|max:10000',
        ] + $this->detailRules();
        $fields = array_filter(array_keys($rules), fn (string $key): bool => ! str_contains($key, '.'));
        $baseRules = ['base' => 'sometimes|array:'.implode(',', $fields)];
        foreach ($rules as $key => $rule) {
            $baseRules["base.$key"] = $rule;
        }
        $input = $this->input($request, $rules + $baseRules, requireFields: true);
        $base = $input['base'] ?? null;
        unset($input['base']);

        if ($input === [] || ($base !== null && (array_diff_key($input, $base) !== [] || array_diff_key($base, $input) !== []))) {
            throw ValidationException::withMessages(['base' => 'Provide an original value for each field in the update.']);
        }

        return $this->data($this->cards->update($request->user()->id, $this->id($request, 'column'), $this->id($request, 'card'), $input, $base));
    }

    private function detailRules(): array
    {
        return [
            'start_date' => 'sometimes|nullable|date_format:Y-m-d|after:0000-12-31',
            'due_date' => 'sometimes|nullable|date_format:Y-m-d|after:0000-12-31',
            'completed' => 'sometimes|required|boolean:strict',
            'labels' => 'sometimes|array|list|max:50',
            'labels.*' => 'required|array:name,color',
            'labels.*.name' => 'required|string|max:50|distinct:ignore_case',
            'labels.*.color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/D',
            'assignee_ids' => 'sometimes|array|list',
            'assignee_ids.*' => 'required|integer:strict|min:1|distinct',
            'checklists' => 'sometimes|array|list|max:20',
            'checklists.*' => 'required|array:id,title,items',
            'checklists.*.id' => 'required|uuid|distinct:ignore_case',
            'checklists.*.title' => 'required|string|max:255',
            'checklists.*.items' => 'present|array|list|max:100',
            'checklists.*.items.*' => 'required|array:id,title,completed',
            'checklists.*.items.*.id' => 'required|uuid|distinct:ignore_case',
            'checklists.*.items.*.title' => 'required|string|max:255',
            'checklists.*.items.*.completed' => 'required|boolean:strict',
        ];
    }

    public function destroy(Request $request): Response
    {
        $this->cards->delete($request->user()->id, $this->id($request, 'column'), $this->id($request, 'card'));

        return response()->noContent();
    }

    public function move(Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        foreach (['previous_card_id', 'next_card_id'] as $neighbor) {
            $value = $raw[$neighbor] ?? null;
            if ($value !== null && (! is_int($value) || $value < 1)) {
                throw ValidationException::withMessages([$neighbor => 'Use a positive integer or null.']);
            }
        }

        $positiveInteger = static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_int($value) || $value < 1) {
                $fail('The :attribute field must be a positive integer.');
            }
        };
        $input = $this->input($request, [
            'column_id' => ['required', $positiveInteger],
            'previous_card_id' => ['present', 'nullable', $positiveInteger],
            'next_card_id' => ['present', 'nullable', $positiveInteger],
        ]);
        $id = $this->id($request, 'card');
        $previous = $input['previous_card_id'];
        $next = $input['next_card_id'];
        if ($previous === $id || $next === $id || ($previous !== null && $previous === $next)) {
            throw ValidationException::withMessages(['neighbors' => 'Choose distinct neighbors other than the moving card.']);
        }

        return $this->data($this->cards->move($request->user()->id, $id, $input['column_id'], $previous, $next));
    }
}
