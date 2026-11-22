<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\Board;
use App\Models\Card;
use App\Repositories\BoardRepository;
use App\Repositories\CardRepository;
use App\Repositories\ColumnRepository;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CardService
{
    private const POSITION_STEP = 1024.0;

    public function __construct(
        private BoardRepository $boards,
        private ColumnRepository $columns,
        private CardRepository $cards,
        private UnitOfWork $transactions,
    ) {}

    public function all(int $owner, int $column): Collection
    {
        $this->columns->find($owner, $column);

        return $this->cards->all($owner, $column);
    }

    public function find(int $owner, int $column, int $id): Card
    {
        return $this->cards->find($owner, $id, $column);
    }

    public function create(int $owner, int $column, array $attributes): Card
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $column, $attributes) {
            $board = $this->lockColumn($owner, $column);
            $last = $this->cards->lastPosition($column);
            $position = $this->positionBetween($last, null);
            if ($position === null) {
                $this->compact($column);
                $position = $this->cards->lastPosition($column) + self::POSITION_STEP;
            }
            $card = $this->cards->create($column, $attributes, $position);
            BoardChanged::dispatch($this->boards->recipients($board), $board->id);

            return $transaction->commit($card);
        });
    }

    public function update(int $owner, int $column, int $id, array $attributes): Card
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $column, $id, $attributes) {
            $board = $this->lockColumn($owner, $column);
            $card = $this->cards->update($this->cards->find($owner, $id, $column), $attributes);
            BoardChanged::dispatch($this->boards->recipients($board), $board->id);

            return $transaction->commit($card);
        });
    }

    public function delete(int $owner, int $column, int $id): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $column, $id) {
            $board = $this->lockColumn($owner, $column);
            $this->cards->delete($this->cards->find($owner, $id, $column));
            BoardChanged::dispatch($this->boards->recipients($board), $board->id);

            return $transaction->commit();
        });
    }

    public function move(int $owner, int $id, int $column, ?int $previousId, ?int $nextId): Card
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $id, $column, $previousId, $nextId) {
            $board = $this->cards->boardId($owner, $id);
            $locked = $this->boards->lock($owner, $board);
            $card = $this->cards->find($owner, $id);
            $target = $this->columns->find($owner, $column);
            if ($target->board_id !== $board) {
                $transaction->rollback(new ConflictHttpException('Cards can only be moved within their board.'));
            }

            $previous = $previousId === null ? null : $this->cards->preceding($column, $id, $previousId);
            $next = $this->cards->following($column, $id, $previous);
            if ($previous?->id !== $previousId || $next?->id !== $nextId) {
                $transaction->rollback(new ConflictHttpException('The destination order has changed.'));
            }

            $position = $this->positionBetween($previous?->position, $next?->position);
            if ($position === null) {
                $this->compact($column, $id);
                $position = $this->positionBetween($previous?->refresh()->position, $next?->refresh()->position);
            }

            $card = $this->cards->place($card, $column, $position);
            BoardChanged::dispatch($this->boards->recipients($locked), $board);

            return $transaction->commit($card);
        });
    }

    private function lockColumn(int $owner, int $column): Board
    {
        $board = $this->boards->lock($owner, $this->columns->find($owner, $column)->board_id);
        $this->columns->find($owner, $column, $board->id);

        return $board;
    }

    private function positionBetween(?float $previous, ?float $next): ?float
    {
        $position = match (true) {
            $previous === null && $next === null => self::POSITION_STEP,
            $previous === null => $next - self::POSITION_STEP,
            $next === null => $previous + self::POSITION_STEP,
            default => $previous / 2 + $next / 2,
        };

        if (! is_finite($position) || ($previous !== null && $position <= $previous) || ($next !== null && $position >= $next)) {
            return null;
        }

        return $position;
    }

    private function compact(int $column, ?int $excluded = null): void
    {
        foreach ($this->cards->ordered($column, $excluded) as $index => $card) {
            $this->cards->place($card, $column, ($index + 1) * self::POSITION_STEP);
        }
    }
}
