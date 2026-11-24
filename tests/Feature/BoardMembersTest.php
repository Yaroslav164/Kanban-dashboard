<?php

declare(strict_types=1);

use App\Events\BoardChanged;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'team-owner@example.test']);
    $this->member = User::query()->create(['name' => 'Member', 'email' => 'team-member@example.test']);
    $this->actingAs($this->owner);
    $this->board = $this->postJson('/api/boards', ['title' => 'Team'])->assertCreated()->json('data.id');
    $this->boardUrl = "/api/boards/{$this->board}";
    $this->membersUrl = "$this->boardUrl/members";
    $this->memberUrl = "$this->membersUrl/{$this->member->id}";
});

it('adds existing accounts, changes their role, and revokes board access', function () {
    $this->postJson($this->membersUrl, ['email' => 'missing@example.test', 'role' => 'editor'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->postJson($this->membersUrl, ['email' => $this->owner->email, 'role' => 'viewer'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->postJson($this->membersUrl, ['email' => $this->member->email, 'role' => 'owner'])
        ->assertUnprocessable()->assertJsonValidationErrors('role');
    $this->postJson($this->membersUrl, ['email' => strtoupper($this->member->email), 'role' => 'editor'])
        ->assertCreated()->assertExactJson(['data' => [
            'id' => $this->member->id, 'name' => $this->member->name, 'email' => $this->member->email, 'role' => 'editor',
        ]]);
    $this->postJson($this->membersUrl, ['email' => $this->member->email, 'role' => 'editor'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->patchJson("$this->membersUrl/{$this->owner->id}", ['role' => 'viewer'])->assertUnprocessable();
    $this->deleteJson("$this->membersUrl/{$this->owner->id}")->assertUnprocessable();
    $this->patchJson($this->memberUrl, ['role' => 'viewer'])->assertOk()->assertJsonPath('data.role', 'viewer');

    $this->actingAs($this->member);
    $this->getJson('/api/boards')->assertOk()->assertJsonPath('data.0.role', 'viewer')->assertJsonMissingPath('data.0.user_id');
    $this->getJson($this->boardUrl)->assertOk()->assertJsonPath('data.role', 'viewer');
    $this->getJson($this->membersUrl)->assertOk()
        ->assertJsonPath('data.0.role', 'owner')->assertJsonPath('data.1.role', 'viewer');
    $this->patchJson($this->memberUrl, ['role' => 'editor'])->assertForbidden();
    $this->deleteJson($this->memberUrl)->assertForbidden();
    $this->postJson($this->membersUrl, ['email' => $this->owner->email, 'role' => 'viewer'])->assertForbidden();

    $this->actingAs($this->owner)->deleteJson($this->memberUrl)->assertNoContent();
    $this->actingAs($this->member);
    $this->getJson('/api/boards')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson($this->boardUrl)->assertNotFound();
    $this->getJson($this->membersUrl)->assertNotFound();
});

it('lets editors change board content and gives viewers read-only access to the whole tree', function () {
    $this->postJson($this->membersUrl, ['email' => $this->member->email, 'role' => 'editor'])->assertCreated();
    $this->actingAs($this->member);
    $this->deleteJson($this->boardUrl)->assertForbidden();
    $this->patchJson($this->boardUrl, ['title' => 'Changed'])->assertOk()->assertJsonPath('data.role', 'editor');
    $column = $this->postJson("$this->boardUrl/columns", ['title' => 'Doing'])->assertCreated()->json('data.id');
    $columnUrl = "$this->boardUrl/columns/$column";
    $target = $this->postJson("$this->boardUrl/columns", ['title' => 'Done'])->assertCreated()->json('data.id');
    $this->patchJson($columnUrl, ['title' => 'In progress'])->assertOk();
    $card = $this->postJson("/api/columns/$column/cards", ['title' => 'Card'])->assertCreated()->json('data.id');
    $cardUrl = "/api/columns/$target/cards/$card";
    $move = ['column_id' => $target, 'previous_card_id' => null, 'next_card_id' => null];
    $this->postJson("/api/cards/$card/move", $move)->assertOk();
    $this->patchJson($cardUrl, ['title' => 'Edited'])->assertOk();

    $this->actingAs($this->owner)->patchJson($this->memberUrl, ['role' => 'viewer'])->assertOk();
    $this->actingAs($this->member);
    $this->getJson("$this->boardUrl/columns")->assertOk();
    $this->getJson($columnUrl)->assertOk();
    $this->getJson("/api/columns/$target/cards")->assertOk();
    $this->getJson($cardUrl)->assertOk()->assertJsonPath('data.title', 'Edited');
    $this->patchJson($this->boardUrl, ['title' => 'Forbidden'])->assertForbidden();
    $this->postJson("$this->boardUrl/columns", ['title' => 'Forbidden'])->assertForbidden();
    $this->patchJson($columnUrl, ['title' => 'Forbidden'])->assertForbidden();
    $this->deleteJson($columnUrl)->assertForbidden();
    $this->postJson("/api/columns/$target/cards", ['title' => 'Forbidden'])->assertForbidden();
    $this->patchJson($cardUrl, ['title' => 'Forbidden'])->assertForbidden();
    $this->deleteJson($cardUrl)->assertForbidden();
    $this->postJson("/api/cards/$card/move", $move)->assertForbidden();

    $this->actingAs($this->owner)->patchJson($this->memberUrl, ['role' => 'editor'])->assertOk();
    $this->actingAs($this->member)->deleteJson($cardUrl)->assertNoContent();
    $this->deleteJson($columnUrl)->assertNoContent();
    $this->actingAs($this->owner)->deleteJson($this->boardUrl)->assertNoContent();
    $this->actingAs($this->member)->getJson($this->membersUrl)->assertNotFound();
});

it('notifies collaborators about changes and sends one last update when access is removed', function () {
    $channels = [];
    Event::listen(BoardChanged::class, function (BoardChanged $event) use (&$channels): void {
        $channels[] = array_column($event->broadcastOn(), 'name');
    });
    $both = ["private-users.{$this->owner->id}", "private-users.{$this->member->id}"];
    $this->postJson($this->membersUrl, ['email' => $this->member->email, 'role' => 'editor'])->assertCreated();
    $this->actingAs($this->member)->patchJson($this->boardUrl, ['title' => 'Edited'])->assertOk();
    $this->actingAs($this->owner)->deleteJson($this->memberUrl)->assertNoContent();
    $this->patchJson($this->boardUrl, ['title' => 'Private'])->assertOk();

    expect($channels)->toBe([$both, $both, $both, [$both[0]]]);
});

it('scopes custom roles to a board and enforces permission changes on existing members', function () {
    $url = "$this->boardUrl/roles";
    $role = $this->postJson($url, ['name' => 'Coordinator', 'permissions' => ['columns.create']])
        ->assertCreated()->json('data');
    $this->postJson($url, ['name' => 'Unknown grant', 'permissions' => ['owner']])->assertUnprocessable();
    $other = $this->postJson('/api/boards', ['title' => 'Other'])->json('data.id');
    $this->postJson("/api/boards/$other/members", ['email' => $this->member->email, 'role' => $role['key']])->assertUnprocessable();
    $this->postJson($this->membersUrl, ['email' => $this->member->email, 'role' => $role['key']])->assertCreated();
    $this->actingAs($this->member);
    $this->getJson($this->boardUrl)->assertJsonPath('data.role', $role['key'])->assertJsonPath('data.permissions', ['columns.create']);
    $this->postJson("$this->boardUrl/columns", ['title' => 'Inbox'])->assertCreated();
    $this->patchJson($this->boardUrl, ['title' => 'Changed'])->assertForbidden();
    $this->patchJson("$url/{$role['id']}", ['name' => 'Escalated', 'permissions' => ['board.rename']])->assertForbidden();
    $this->actingAs($this->owner)->patchJson("$url/{$role['id']}", ['name' => 'Read only', 'permissions' => []])->assertOk();
    $this->deleteJson("$url/{$role['id']}")->assertUnprocessable();
    $this->actingAs($this->member)->postJson("$this->boardUrl/columns", ['title' => 'Denied'])->assertForbidden();
    $this->actingAs($this->owner)->patchJson($this->memberUrl, ['role' => 'viewer'])->assertOk();
    $this->deleteJson("$url/{$role['id']}")->assertNoContent();
});

it('isolates custom roles from other boards and rejects unknown or duplicate permissions', function () {
    $url = "$this->boardUrl/roles";
    $payload = ['name' => 'Coordinator', 'permissions' => ['cards.edit']];
    $role = $this->postJson($url, $payload)->assertCreated()->json('data');
    $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson($url, ['name' => 'Duplicate grants', 'permissions' => ['cards.edit', 'cards.edit']])
        ->assertUnprocessable()->assertJsonValidationErrors('permissions.1');
    $this->postJson($url, ['name' => 'Unsupported grant', 'permissions' => ['roles.manage']])
        ->assertUnprocessable()->assertJsonValidationErrors('permissions.0');

    $other = $this->postJson('/api/boards', ['title' => 'Other'])->assertCreated()->json('data.id');
    $otherRoles = "/api/boards/$other/roles";
    $otherRole = $this->postJson($otherRoles, $payload)->assertCreated()->json('data');
    $this->patchJson("$otherRoles/{$role['id']}", $payload)->assertNotFound();
    $this->deleteJson("$otherRoles/{$role['id']}")->assertNotFound();
    $this->getJson($otherRoles)->assertExactJson(['data' => [$otherRole]]);

    $this->actingAs($this->member)->getJson($url)->assertNotFound();
    $this->actingAs($this->owner)->postJson($this->membersUrl, ['email' => $this->member->email, 'role' => 'viewer'])->assertCreated();
    $this->patchJson($this->memberUrl, ['role' => $otherRole['key']])
        ->assertUnprocessable()->assertJsonValidationErrors('role');

    expect(fn () => DB::transaction(fn () => DB::table('board_members')
        ->where('board_id', $this->board)
        ->where('user_id', $this->member->id)
        ->update(['custom_role_id' => $otherRole['id']])))->toThrow(QueryException::class);

    $this->actingAs($this->member)->getJson($url)->assertExactJson(['data' => [$role]]);
    $this->deleteJson("$url/{$role['id']}")->assertForbidden();
    $this->postJson($url, ['name' => 'Escalated', 'permissions' => ['cards.delete']])->assertForbidden();
});
