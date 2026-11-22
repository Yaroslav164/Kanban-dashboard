<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SessionService
{
    public function __construct(private UserRepository $users, private UnitOfWork $work) {}

    public function register(array $input): User
    {
        try {
            $user = $this->work->run(fn ($tx) => $tx->commit($this->users->create([
                'name' => $input['name'], 'email' => mb_strtolower($input['email']), 'password' => Hash::make($input['password']),
            ])));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'This email is already registered.']);
        }

        Auth::guard('web')->login($user);

        return $user;
    }

    public function login(array $credentials): User
    {
        $credentials['email'] = mb_strtolower($credentials['email']);
        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'The credentials are invalid.']);
        }

        return Auth::guard('web')->user();
    }
}
