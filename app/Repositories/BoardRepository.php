<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Board;
use App\Models\Card;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class BoardRepository
{
    public function all(int $owner): Collection
    {
        return $this->owned($owner)->orderBy('id')->get();
    }

    public function find(int $owner, int $id): Board
    {
        return $this->owned($owner)->with('columns.cards')->findOrFail($id);
    }

    public function requireOwned(int $owner, int $id): void
    {
        $this->owned($owner)->select('id')->findOrFail($id);
    }

    public function lock(int $owner, int $id): Board
    {
        return $this->owned($owner)->lockForUpdate()->findOrFail($id);
    }

    public function create(int $owner, string $title): Board
    {
        return Board::query()->create(['user_id' => $owner, 'title' => $title]);
    }

    public function rename(Board $board, string $title): Board
    {
        $board->update(['title' => $title]);

        return $board;
    }

    public function delete(Board $board): void
    {
        $columns = $board->columns()->getQuery()->reorder();
        Card::query()->whereIn('column_id', (clone $columns)->select('id'))->delete();
        $columns->delete();
        $board->delete();
    }

    private function owned(int $owner): Builder
    {
        return Board::query()->where('user_id', $owner);
    }
}
