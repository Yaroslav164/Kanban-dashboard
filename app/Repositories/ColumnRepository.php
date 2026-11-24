<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ColumnRepository
{
    public function all(int $owner, int $board): Collection
    {
        return $this->accessible($owner)->where('board_id', $board)->orderBy('position')->orderBy('id')->get();
    }

    public function find(int $owner, int $id, ?int $board = null): Column
    {
        $columns = $this->accessible($owner);
        if ($board !== null) {
            $columns->where('board_id', $board);
        }

        return $columns->findOrFail($id);
    }

    public function create(int $board, string $title, ?int $wipLimit = null): Column
    {
        $last = Column::query()->where('board_id', $board)->max('position');

        return Column::query()->create([
            'board_id' => $board, 'title' => $title, 'position' => ($last ?? 0) + 1, 'wip_limit' => $wipLimit,
        ]);
    }

    /** @param array{title?: string, wip_limit?: int|null} $attributes */
    public function update(Column $column, array $attributes): Column
    {
        $column->update($attributes);

        return $column;
    }

    public function delete(Column $column): void
    {
        $column->cards()->getQuery()->reorder()->delete();
        $column->delete();
    }

    private function accessible(int $user): Builder
    {
        return Column::query()->whereHas('board', fn (Builder $boards) => $boards->accessibleTo($user));
    }
}
