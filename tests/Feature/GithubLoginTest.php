<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\User as GithubUser;

uses(RefreshDatabase::class);

function githubProfile(string $id = '314'): GithubUser
{
    return (new GithubUser)->map(['id' => $id, 'name' => 'Test User', 'nickname' => 'tester'])->setToken('fixture');
}

function githubCallback($test, ?User $linkTo = null)
{
    return $test->withSession([
        'state' => 'expected', 'github_flow' => ['intent' => $linkTo ? 'link' : 'login', 'user' => $linkTo?->id],
    ])->get('/auth/github/callback?state=expected&code=ok');
}

function fakeGithubProfile(): void
{
    $driver = Mockery::mock(GithubProvider::class);
    $driver->shouldReceive('user')->andReturn(githubProfile());
    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);
}

it('starts the provider flow and validates link intent and callback state', function () {
    config(['services.github' => ['client_id' => 'local', 'client_secret' => 'test', 'redirect' => 'http://localhost/auth/github/callback']]);
    $this->get('/auth/github/redirect?intent=link')->assertUnauthorized();
    $this->get('/auth/github/redirect?intent=bogus')->assertUnprocessable();
    $response = $this->get('/auth/github/redirect')->assertRedirect();
    expect(parse_url($response->headers->get('Location'), PHP_URL_HOST))->toBe('github.com');
    $this->get('/auth/github/callback?state=incorrect&code=code')->assertRedirect('/login?oauth=invalid_state');
    $this->withSession(['state' => 's', 'github_flow' => ['intent' => 'login', 'user' => null]])
        ->get('/auth/github/callback?state=s&error=access_denied')->assertRedirect('/login?oauth=denied');
    $this->get('/auth/github/callback?state=s&code=code')->assertRedirect('/login?oauth=invalid_state');
});

it('creates an account only from a verified primary email', function () {
    fakeGithubProfile();
    Http::fake(['api.github.com/user/emails' => Http::response([
        ['email' => 'private@example.test', 'primary' => false, 'verified' => true],
        ['email' => 'PRIMARY@example.test', 'primary' => true, 'verified' => true],
    ])]);
    githubCallback($this)->assertRedirect('/boards');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'primary@example.test', 'github_id' => 314, 'password' => null]);
});

it('refuses unavailable or unverified email information', function (array $emails, int $status, string $error) {
    fakeGithubProfile();
    Http::fake(['api.github.com/user/emails' => Http::response($emails, $status)]);
    githubCallback($this)->assertRedirect('/login?oauth='.$error);
    $this->assertGuest();
})->with([
    [[], 503, 'provider_unavailable'],
    [[['email' => 'u@example.test', 'primary' => true, 'verified' => false]], 200, 'email_unverified'],
]);

it('requires an explicit link before an existing local account can use the provider', function () {
    $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password-123']);
    fakeGithubProfile();
    Http::fake(['api.github.com/user/emails' => Http::response([['email' => $owner->email, 'primary' => true, 'verified' => true]])]);
    githubCallback($this)->assertRedirect('/login?oauth=account_exists');
    $this->assertGuest();
    $this->actingAs($owner);
    githubCallback($this, $owner)->assertRedirect('/boards');
    $this->assertDatabaseHas('users', ['id' => $owner->id, 'github_id' => 314]);
    $this->postJson('/api/auth/logout')->assertNoContent();
    githubCallback($this)->assertRedirect('/boards');
    $this->assertAuthenticatedAs($owner);
});

it('refuses a link already taken by a different user', function () {
    User::query()->create(['name' => 'One', 'email' => 'one@example.test', 'github_id' => 314]);
    $user = User::query()->create(['name' => 'Two', 'email' => 'two@example.test']);
    $this->actingAs($user);
    fakeGithubProfile();
    githubCallback($this, $user)->assertRedirect('/settings/account?oauth=identity_in_use');
    $this->assertDatabaseHas('users', ['id' => $user->id, 'github_id' => null]);
});
