<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class KanbanController
{
    protected function id(Request $request, string $name): int
    {
        $id = filter_var($request->route($name), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404, 'Resource not found.');

        return $id;
    }

    protected function input(Request $request, array $rules, bool $requireFields = false): array
    {
        $input = $request->json()->all();
        $unknown = array_diff_key($input, $rules);
        if ($unknown !== []) {
            throw ValidationException::withMessages(array_fill_keys(array_keys($unknown), 'This field is not allowed.'));
        }
        if ($requireFields && $input === []) {
            throw ValidationException::withMessages(['body' => 'Provide at least one field.']);
        }

        return Validator::make($input, $rules)->validate();
    }

    protected function data(object|array $value, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $value], $status);
    }
}
