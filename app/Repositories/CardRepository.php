<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Card;
use App\Models\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CardRepository
{
    public function all(int $owner, int $column): Collection
    {
        return $this->accessible($owner)->where('column_id', $column)->orderBy('position')->orderBy('id')->get();
    }

    public function find(int $owner, int $id, ?int $column = null): Card
    {
        $cards = $this->accessible($owner);
        if ($column !== null) {
            $cards->where('column_id', $column);
        }

        return $cards->findOrFail($id);
    }

    public function boardId(int $owner, int $id): int
    {
        $card = $this->accessible($owner)->addSelect([
            'board_id' => Column::query()->select('board_id')->whereColumn('columns.id', 'cards.column_id'),
        ])->findOrFail($id);

        return (int) $card->board_id;
    }

    public function findOnBoard(int $board, int $id): Card
    {
        return Card::query()->whereHas('column', fn (Builder $columns) => $columns->where('board_id', $board))
            ->findOrFail($id);
    }

    public function create(int $column, array $attributes, float $position): Card
    {
        return Card::query()->create($attributes + ['column_id' => $column, 'position' => $this->encodePosition($position)]);
    }

    public function update(Card $card, array $attributes): Card
    {
        $card->update($attributes);

        return $card;
    }

    public function delete(Card $card): void
    {
        $card->delete();
    }

    public function removeAssignee(int $board, int $user): void
    {
        DB::table('card_assignees')->where('user_id', $user)
            ->whereIn('card_id', Card::withoutGlobalScope(Card::ACTIVE_SCOPE)->select('id')
                ->whereHas('column', fn (Builder $columns) => $columns->withTrashed()->where('board_id', $board)))
            ->delete();
    }

    /** @return list<array{id: int, title: string, column_id: int, column_title: string}> */
    public function archivedOnBoard(int $user, int $board): array
    {
        return $this->accessibleIncludingArchived($user)
            ->whereHas('column', fn (Builder $columns) => $columns->withTrashed()->where('board_id', $board))
            ->whereNotNull('cards.archived_at')
            ->orderByDesc('cards.archived_at')
            ->orderByDesc('cards.id')
            ->get()
            ->map(fn (Card $card): array => [
                'id' => $card->id,
                'title' => $card->title,
                'column_id' => $card->column_id,
                'column_title' => $card->column->title,
            ])
            ->all();
    }

    public function findIncludingArchived(int $user, int $id): Card
    {
        return $this->accessibleIncludingArchived($user)->findOrFail($id);
    }

    public function archive(Card $card): void
    {
        $card->forceFill(['archived_at' => now()])->save();
    }

    public function restore(Card $card, int $column, float $position): void
    {
        $card->forceFill([
            'column_id' => $column, 'position' => $this->encodePosition($position), 'archived_at' => null,
        ])->save();
    }

    public function lastPosition(int $column): ?float
    {
        $position = Card::query()->where('column_id', $column)->max('position');

        return $position === null ? null : (float) $position;
    }

    public function place(Card $card, int $column, float $position): Card
    {
        return $this->update($card, ['column_id' => $column, 'position' => $this->encodePosition($position)]);
    }

    public function preceding(int $column, int $moving, int $previous): ?Card
    {
        return $this->destination($column, $moving)->find($previous);
    }

    public function following(int $column, int $moving, ?Card $previous): ?Card
    {
        $cards = $this->destination($column, $moving);
        if ($previous !== null) {
            $cards->whereRaw('("position", "id") > (?, ?)', [$this->encodePosition($previous->position), $previous->id]);
        }

        return $cards->orderBy('position')->orderBy('id')->first();
    }

    public function ordered(int $column, ?int $excluding = null): Collection
    {
        return $this->destination($column, $excluding)->orderBy('position')->orderBy('id')->get();
    }

    private function destination(int $column, ?int $excluding): Builder
    {
        $cards = Card::query()->where('column_id', $column);
        if ($excluding !== null) {
            $cards->whereKeyNot($excluding);
        }

        return $cards;
    }

    private function accessible(int $user): Builder
    {
        return Card::query()->whereHas('column.board', fn (Builder $boards) => $boards->accessibleTo($user));
    }

    private function accessibleIncludingArchived(int $user): Builder
    {
        return Card::withoutGlobalScope(Card::ACTIVE_SCOPE)
            ->withOnly(['column' => fn ($column) => $column->withTrashed()])
            ->whereHas('column', fn (Builder $columns) => $columns->withTrashed()
                ->whereHas('board', fn (Builder $boards) => $boards->accessibleTo($user)));
    }

    private function encodePosition(float $position): string
    {
        // PDO binds doubles as text. Keep all round-trip digits independently of PHP's display precision.
        return sprintf('%.17H', $position);
    }
}
