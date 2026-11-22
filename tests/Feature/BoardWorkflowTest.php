<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::query()->forceCreate([
        'name' => 'Owner', 'email' => 'owner@example.test', 'password' => null,
    ]);
    $this->actingAs($this->owner);
});

function createBoardTree($test): array
{
    $board = $test->postJson('/api/boards', ['title' => 'Release'])->assertCreated()->json('data.id');
    $column = $test->postJson("/api/boards/$board/columns", ['title' => 'Doing'])->assertCreated()->json('data.id');
    $card = $test->postJson("/api/columns/$column/cards", ['title' => 'Ship'])->assertCreated()->json('data.id');

    return [$board, $column, $card];
}

it('creates, reads, edits and soft-deletes an owned tree', function () {
    [$board, $column, $card] = createBoardTree($this);
    $this->getJson('/api/boards')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.user_id');
    $this->patchJson("/api/boards/$board", ['title' => 'Sprint'])->assertOk()->assertJsonPath('data.title', 'Sprint');
    $this->getJson("/api/boards/$board/columns")->assertOk()->assertJsonPath('data.0.id', $column);
    $this->patchJson("/api/boards/$board/columns/$column", ['title' => 'Todo'])->assertOk();
    $this->getJson("/api/boards/$board/columns/$column")->assertOk()->assertJsonPath('data.title', 'Todo');
    $this->getJson("/api/columns/$column/cards")->assertOk()->assertJsonPath('data.0.description', null);
    $this->patchJson("/api/columns/$column/cards/$card", ['description' => 'Next'])->assertOk();
    $this->getJson("/api/columns/$column/cards/$card")->assertOk()->assertJsonPath('data.description', 'Next');
    $this->getJson("/api/boards/$board")->assertOk()->assertJsonPath('data.columns.0.cards.0.id', $card);
    $this->deleteJson("/api/boards/$board")->assertNoContent();
    foreach (['boards' => $board, 'columns' => $column, 'cards' => $card] as $table => $id) {
        $this->assertSoftDeleted($table, ['id' => $id]);
    }
    $this->getJson("/api/columns/$column/cards")->assertNotFound();
    $this->getJson('/api/boards')->assertOk()->assertJsonCount(0, 'data');
});

it('rejects unknown, empty, and server-controlled input', function () {
    [$board, $column, $card] = createBoardTree($this);
    $this->postJson('/api/boards', ['title' => '   '])->assertUnprocessable();
    $this->patchJson("/api/boards/$board", [])->assertUnprocessable();
    $this->patchJson("/api/boards/$board", ['title' => 'X', 'user_id' => 42])->assertUnprocessable();
    $this->patchJson("/api/boards/$board/columns/$column", ['position' => 2])->assertUnprocessable();
    $this->patchJson("/api/columns/$column/cards/$card", ['description' => str_repeat('x', 10001)])->assertUnprocessable();
    $this->patchJson("/api/columns/$column/cards/$card", ['description' => null])->assertOk();
});

it('checks ownership and the requested parent on nested routes', function () {
    [$board, $column, $card] = createBoardTree($this);
    [$otherBoard, $otherColumn] = createBoardTree($this);
    $this->getJson("/api/boards/$otherBoard/columns/$column")->assertNotFound();
    $this->deleteJson("/api/columns/$otherColumn/cards/$card")->assertNotFound();
    $this->actingAs(User::query()->forceCreate(['name' => 'Other', 'email' => 'other@example.test']));
    $this->getJson('/api/boards')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/boards/$board")->assertNotFound();
    $this->getJson("/api/boards/$board/columns")->assertNotFound();
    $this->postJson("/api/columns/$column/cards", ['title' => 'X'])->assertNotFound();
    $this->deleteJson("/api/columns/$column/cards/$card")->assertNotFound();
    $this->deleteJson("/api/boards/$board")->assertNotFound();
});

it('soft-deletes a card or a column independently', function () {
    [$board, $column, $card] = createBoardTree($this);
    $this->deleteJson("/api/columns/$column/cards/$card")->assertNoContent();
    $this->deleteJson("/api/columns/$column/cards/$card")->assertNotFound();
    $second = $this->postJson("/api/columns/$column/cards", ['title' => 'Two'])->assertCreated()->json('data.id');
    $this->deleteJson("/api/boards/$board/columns/$column")->assertNoContent();
    $this->assertSoftDeleted('cards', ['id' => $second]);
    $this->getJson("/api/boards/$board")->assertOk()->assertJsonCount(0, 'data.columns');
});

it('preserves fractional ordering across moves, retries and precision exhaustion', function () {
    [$board, $column, $moving] = createBoardTree($this);
    $target = $this->postJson("/api/boards/$board/columns", ['title' => 'Done'])->assertCreated()->json('data.id');
    $first = $this->postJson("/api/columns/$target/cards", ['title' => 'A'])->assertCreated()->json('data.id');
    $last = $this->postJson("/api/columns/$target/cards", ['title' => 'B'])->assertCreated()->json('data.id');
    $move = fn ($previous, $next) => $this->postJson("/api/cards/$moving/move", [
        'column_id' => $target, 'previous_card_id' => $previous, 'next_card_id' => $next,
    ]);
    $move($first, $last)->assertOk()->assertJsonPath('data.column_id', $target);
    $move($first, $last)->assertOk();
    $move(null, $last)->assertConflict();
    $move($moving, $last)->assertUnprocessable();
    $move($first, $first)->assertUnprocessable();
    $move(null, $first)->assertOk();
    $move($last, null)->assertOk();
    DB::table('cards')->where('id', $first)->update(['position' => 1]);
    DB::table('cards')->where('id', $last)->update(['position' => '1.0000000000000002']);
    $move($first, $last)->assertOk();
    $cards = $this->getJson("/api/columns/$target/cards")->assertOk()->json('data');
    expect(array_column($cards, 'id'))->toBe([$first, $moving, $last]);
    expect($cards[0]['position'])->toBeLessThan($cards[1]['position']);
    expect($cards[1]['position'])->toBeLessThan($cards[2]['position']);
    $this->getJson("/api/columns/$column/cards")->assertOk()->assertJsonCount(0, 'data');
});

it('handles empty targets, cross-board and inaccessible moves', function () {
    [$board, $column, $card] = createBoardTree($this);
    [, $otherColumn] = createBoardTree($this);
    $move = fn ($target) => $this->postJson("/api/cards/$card/move", [
        'column_id' => $target, 'previous_card_id' => null, 'next_card_id' => null,
    ]);
    $move($otherColumn)->assertConflict();
    $move(999999)->assertNotFound();
    $target = $this->postJson("/api/boards/$board/columns", ['title' => 'Empty'])->assertCreated()->json('data.id');
    $move($target)->assertOk();
    $move($target)->assertOk();
    $this->postJson("/api/cards/$card/move", ['column_id' => $column])->assertUnprocessable();
});
