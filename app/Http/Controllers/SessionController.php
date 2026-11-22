<?php

namespace App\Http\Controllers;

use App\Services\SessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController
{
    public function __construct(private SessionService $sessions) {}

    public function register(Request $request)
    {
        $input = $this->validateInput($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['bail', 'required', 'string', 'min:8', 'confirmed', $this->validBcryptPassword(...)],
            'password_confirmation' => ['required', 'string'],
        ]);
        $user = $this->sessions->register($input);
        $request->session()->regenerate();

        return response()->json(['data' => $user], 201);
    }

    public function login(Request $request)
    {
        $input = $this->validateInput($request, [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['bail', 'required', 'string', $this->validBcryptPassword(...)],
        ]);
        $user = $this->sessions->login($input);
        $request->session()->regenerate();

        return response()->json(['data' => $user]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()]);
    }

    private function validateInput(Request $request, array $rules): array
    {
        foreach (array_diff(array_keys($request->all()), array_keys($rules)) as $key) {
            throw ValidationException::withMessages([$key => 'Unexpected field.']);
        }

        return $request->validate($rules);
    }

    private function validBcryptPassword(string $attribute, string $value, Closure $fail): void
    {
        if (strlen($value) > 72 || str_contains($value, "\0")) {
            $fail('The password must contain at most 72 bytes and no null characters.');
        }
    }
}
