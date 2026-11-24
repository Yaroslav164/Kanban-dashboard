<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets editors set or clear the limit independently of the column title', function () {
    $owner = User::query()->forceCreate(['name' => 'Owner', 'email' => 'owner@example.test']);
    $viewer = User::query()->forceCreate(['name' => 'Viewer', 'email' => 'viewer@example.test']);
    $this->actingAs($owner);
    $board = $this->postJson('/api/boards', ['title' => 'Release'])->assertCreated()->json('data.id');
    $column = $this->postJson("/api/boards/$board/columns", ['title' => 'Doing', 'wip_limit' => 2])
        ->assertCreated()->assertJsonPath('data.wip_limit', 2)->json('data.id');
    $url = "/api/boards/$board/columns/$column";
    foreach ([0, -1, 1.5, 2147483648, '1', true] as $invalid) {
        $this->patchJson($url, ['wip_limit' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('wip_limit');
    }

    $this->patchJson($url, ['wip_limit' => 1])->assertOk()->assertJsonPath('data.title', 'Doing')->assertJsonPath('data.wip_limit', 1);
    $this->patchJson($url, ['title' => 'Review'])->assertOk()->assertJsonPath('data.wip_limit', 1);
    $this->postJson("/api/columns/$column/cards", ['title' => 'One'])->assertCreated();
    $this->postJson("/api/columns/$column/cards", ['title' => 'Two'])->assertCreated();
    $this->getJson("/api/boards/$board")->assertOk()->assertJsonPath('data.columns.0.wip_limit', 1)
        ->assertJsonCount(2, 'data.columns.0.cards');
    $this->postJson("/api/boards/$board/members", ['email' => $viewer->email, 'role' => 'viewer'])->assertCreated();
    $this->actingAs($viewer)->patchJson($url, ['wip_limit' => null])->assertForbidden();
    $this->actingAs($owner)->patchJson($url, ['wip_limit' => null])->assertOk()->assertJsonPath('data.wip_limit', null);
});
