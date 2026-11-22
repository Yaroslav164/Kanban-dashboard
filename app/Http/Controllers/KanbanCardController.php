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
        ]);

        return $this->data($this->cards->create($request->user()->id, $this->id($request, 'column'), $input), 201);
    }

    public function show(Request $request): JsonResponse
    {
        return $this->data($this->cards->find($request->user()->id, $this->id($request, 'column'), $this->id($request, 'card')));
    }

    public function update(Request $request): JsonResponse
    {
        $input = $this->input($request, [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|max:10000',
        ], requireFields: true);

        return $this->data($this->cards->update($request->user()->id, $this->id($request, 'column'), $this->id($request, 'card'), $input));
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
