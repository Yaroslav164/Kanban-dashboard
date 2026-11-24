<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::query()->forceCreate(['name' => 'Owner', 'email' => 'owner@example.test']);
    $this->actingAs($this->owner);
    $this->board = $this->postJson('/api/boards', ['title' => 'Release'])->assertCreated()->json('data.id');
    $this->column = $this->postJson("/api/boards/$this->board/columns", ['title' => 'Doing'])->assertCreated()->json('data.id');
    $this->card = $this->postJson("/api/columns/$this->column/cards", ['title' => 'Ship'])->assertCreated()->json('data.id');
});

it('hides archived cards from active APIs and restores them at the end of their original column', function () {
    $this->postJson("/api/cards/$this->card/archive")->assertNoContent();
    $this->postJson("/api/cards/$this->card/archive")->assertNoContent();
    $this->getJson("/api/boards/$this->board")->assertOk()->assertJsonCount(0, 'data.columns.0.cards');
    $this->getJson("/api/columns/$this->column/cards")->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/columns/$this->column/cards/$this->card")->assertNotFound();
    $this->getJson("/api/cards/$this->card/activity")->assertNotFound();
    $this->getJson("/api/boards/$this->board/archive")->assertOk()->assertExactJson(['data' => [[
        'id' => $this->card, 'title' => 'Ship', 'column_id' => $this->column, 'column_title' => 'Doing',
    ]]]);

    $alternate = $this->postJson("/api/boards/$this->board/columns", ['title' => 'Archive fallback'])
        ->assertCreated()->json('data.id');
    $this->postJson("/api/cards/$this->card/restore", ['column_id' => $alternate])->assertConflict();
    $next = $this->postJson("/api/columns/$this->column/cards", ['title' => 'Next'])->assertCreated()->json('data.id');
    DB::table('cards')->where('id', $next)->update(['position' => 1.0e30]);
    $this->postJson("/api/cards/$this->card/restore")->assertNoContent();
    $this->postJson("/api/cards/$this->card/restore")->assertNoContent();
    $this->getJson("/api/columns/$this->column/cards")->assertOk()
        ->assertJsonPath('data.0.id', $next)->assertJsonPath('data.1.id', $this->card);
    $this->getJson("/api/boards/$this->board/archive")->assertOk()->assertJsonCount(0, 'data');
});

it('allows an archived card from a deleted column to be restored only into the same board', function () {
    $this->postJson("/api/cards/$this->card/archive")->assertNoContent();
    $member = User::query()->forceCreate(['name' => 'Member', 'email' => 'member@example.test']);
    $this->postJson("/api/boards/$this->board/members", ['email' => $member->email, 'role' => 'viewer'])->assertCreated();
    DB::table('card_assignees')->insert(['card_id' => $this->card, 'user_id' => $member->id]);
    $this->deleteJson("/api/boards/$this->board/columns/$this->column")->assertNoContent();
    $this->deleteJson("/api/boards/$this->board/members/$member->id")->assertNoContent();
    $this->getJson("/api/boards/$this->board/archive")->assertOk()->assertJsonPath('data.0.column_title', 'Doing');
    $this->postJson("/api/cards/$this->card/restore")->assertConflict();
    foreach ([0, '1', true] as $invalid) {
        $this->postJson("/api/cards/$this->card/restore", ['column_id' => $invalid])
            ->assertUnprocessable()->assertJsonValidationErrors('column_id');
    }
    $target = $this->postJson("/api/boards/$this->board/columns", ['title' => 'Backlog'])->assertCreated()->json('data.id');
    $other = $this->postJson('/api/boards', ['title' => 'Other'])->assertCreated()->json('data.id');
    $foreign = $this->postJson("/api/boards/$other/columns", ['title' => 'Other'])->assertCreated()->json('data.id');
    $this->postJson("/api/cards/$this->card/restore", ['column_id' => $foreign])->assertNotFound();
    $this->postJson("/api/cards/$this->card/restore", ['column_id' => $target])->assertNoContent();
    $this->getJson("/api/columns/$target/cards/$this->card")->assertOk()->assertJsonPath('data.assignee_ids', []);
});

it('exposes the archive only to board participants and restricts writes to cards.delete', function () {
    $viewer = User::query()->forceCreate(['name' => 'Viewer', 'email' => 'viewer@example.test']);
    $outsider = User::query()->forceCreate(['name' => 'Outsider', 'email' => 'outsider@example.test']);
    $this->postJson("/api/boards/$this->board/members", ['email' => $viewer->email, 'role' => 'viewer'])->assertCreated();

    $this->actingAs($viewer)->postJson("/api/cards/$this->card/archive")->assertForbidden();
    $this->actingAs($this->owner)->postJson("/api/cards/$this->card/archive")->assertNoContent();
    $this->actingAs($viewer)->getJson("/api/boards/$this->board/archive")->assertOk()->assertJsonCount(1, 'data');
    $this->postJson("/api/cards/$this->card/restore")->assertForbidden();
    $this->actingAs($outsider)->getJson("/api/boards/$this->board/archive")->assertNotFound();
    $this->postJson("/api/cards/$this->card/restore")->assertNotFound();
    $this->actingAs($this->owner)->deleteJson("/api/boards/$this->board/columns/$this->column")->assertNoContent();
    $this->deleteJson("/api/boards/$this->board")->assertNoContent();
    $this->assertSoftDeleted('cards', ['id' => $this->card]);
});
