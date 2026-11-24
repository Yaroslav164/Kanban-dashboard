<?php

declare(strict_types=1);

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists incomplete assigned cards across owned and accessible boards only', function () {
    $this->getJson('/api/me/tasks')->assertUnauthorized();

    $me = User::query()->create(['name' => 'Me', 'email' => 'me@example.test']);
    $other = User::query()->create(['name' => 'Other', 'email' => 'other@example.test']);
    $makeCard = function (User $owner, string $title, bool $shared = false) use ($me) {
        $board = Board::query()->create(['user_id' => $owner->id, 'title' => $title]);
        if ($shared) {
            $board->members()->attach($me->id, ['role' => 'viewer']);
        }
        $column = $board->columns()->create(['title' => 'Doing', 'position' => 1]);
        $card = $column->cards()->create(['title' => $title, 'position' => 1]);
        $card->assignees()->attach($me->id);

        return [$board, $column, $card];
    };

    [$mine, $column, $task] = $makeCard($me, 'Personal');
    $task->update(['due_date' => '2026-12-01']);
    [$shared, , $sharedTask] = $makeCard($other, 'Shared', true);
    $makeCard($other, 'Private');
    $makeCard($me, 'Complete')[2]->update(['completed' => true]);
    $makeCard($me, 'Deleted column')[1]->delete();
    $makeCard($me, 'Deleted board')[0]->delete();
    $makeCard($me, 'Deleted card')[2]->delete();
    $makeCard($me, 'Archived')[2]->forceFill(['archived_at' => now()])->save();
    $makeCard($me, 'Unassigned')[2]->assignees()->detach();

    $this->actingAs($me)->getJson('/api/me/tasks')->assertOk()->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0', [
            'id' => $task->id, 'title' => 'Personal', 'board_id' => $mine->id, 'board_title' => 'Personal',
            'column_id' => $column->id, 'column_title' => 'Doing', 'due_date' => '2026-12-01',
        ])->assertJsonPath('data.1.id', $sharedTask->id)->assertJsonPath('data.1.due_date', null);
    $shared->members()->detach($me->id);
    $this->getJson('/api/me/tasks')->assertOk()->assertJsonCount(1, 'data');
});
