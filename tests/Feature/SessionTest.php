<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('registers, authenticates and ends a first-party session', function () {
    $credentials = ['email' => 'USER@example.test', 'password' => 'secret-password'];
    $this->getJson('/api/auth/me')->assertUnauthorized();
    $this->get('/sanctum/csrf-cookie')->assertNoContent()->assertCookie('XSRF-TOKEN');
    $this->postJson('/api/auth/register', $credentials + ['name' => 'User', 'password_confirmation' => $credentials['password']])
        ->assertCreated()->assertJsonPath('data.email', 'user@example.test')->assertJsonMissingPath('data.password');
    $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.name', 'User');
    $this->postJson('/api/auth/logout')->assertNoContent();
    $this->postJson('/api/auth/login', ['email' => $credentials['email'], 'password' => 'wrong'])->assertUnprocessable();
    $this->postJson('/api/auth/login', $credentials)->assertOk()->assertJsonPath('data.name', 'User');
});

it('rejects invalid registration and non-contract fields', function () {
    $this->postJson('/api/auth/register', ['name' => 'User', 'email' => 'invalid', 'password' => 'x'])
        ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    $this->postJson('/api/auth/login', ['email' => 'u@example.test', 'password' => '123', 'admin' => true])
        ->assertUnprocessable()->assertJsonValidationErrors('admin');
});

it('rejects passwords bcrypt cannot safely hash or compare', function (string $password) {
    $credentials = ['email' => 'user@example.test', 'password' => $password];
    $this->postJson('/api/auth/register', $credentials + ['name' => 'User', 'password_confirmation' => $password])
        ->assertUnprocessable()->assertJsonValidationErrors('password');
    $this->postJson('/api/auth/login', $credentials)
        ->assertUnprocessable()->assertJsonValidationErrors('password');
})->with(["password\0hidden", str_repeat('é', 37)]);

it('treats a password that resembles a bcrypt hash as plaintext', function () {
    $password = Hash::make('original-password');
    $credentials = ['email' => 'user@example.test', 'password' => $password];
    $this->postJson('/api/auth/register', $credentials + ['name' => 'User', 'password_confirmation' => $password])->assertCreated();
    $this->postJson('/api/auth/logout')->assertNoContent();
    $this->postJson('/api/auth/login', [...$credentials, 'password' => 'original-password'])->assertUnprocessable();
    $this->postJson('/api/auth/login', $credentials)->assertOk();
});
