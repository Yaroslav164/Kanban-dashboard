<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\Board;
use App\Models\Card;
use App\Repositories\BoardRepository;
use App\Repositories\CardRepository;
use App\Repositories\CardTemplateRepository;
use App\Repositories\ColumnRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CardService
{
    private const POSITION_STEP = 1024.0;

    public function __construct(
        private BoardRepository $boards,
        private ColumnRepository $columns,
        private CardRepository $cards,
        private CardTemplateRepository $templates,
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
            $board = $this->lockColumn($owner, $column, 'cards.create');

            return $transaction->commit($this->append($board, $column, $attributes));
        });
    }

    public function createFromTemplate(int $owner, int $column, int $templateId): Card
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $column, $templateId) {
            $board = $this->lockColumn($owner, $column, 'cards.create');
            $template = $this->templates->find($board->id, $templateId);
            $checklists = array_map(static fn (array $checklist): array => [
                'id' => (string) Str::uuid(),
                'title' => $checklist['title'],
                'items' => array_map(static fn (array $item): array => [
                    'id' => (string) Str::uuid(),
                    'title' => $item['title'],
                    'completed' => false,
                ], $checklist['items']),
            ], $template->checklists);

            return $transaction->commit($this->append($board, $column, [
                'title' => $template->title,
                'description' => $template->description,
                'labels' => $template->labels,
                'checklists' => $checklists,
            ]));
        });
    }

    private function append(Board $board, int $column, array $attributes): Card
    {
        $last = $this->cards->lastPosition($column);
        $position = $this->positionBetween($last, null);
        if ($position === null) {
            $this->compact($column);
            $position = $this->cards->lastPosition($column) + self::POSITION_STEP;
        }
        $this->validateDates(new Card, $attributes);
        $assignees = $attributes['assignee_ids'] ?? null;
        unset($attributes['assignee_ids']);
        $card = $this->cards->create($column, $attributes, $position);
        $this->syncAssignees($card, $board, $assignees);
        BoardChanged::dispatch($this->boards->recipients($board), $board->id);

        return $card;
    }

    public function update(int $owner, int $column, int $id, array $attributes, ?array $base = null): Card
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $column, $id, $attributes, $base) {
            $board = $this->lockColumn($owner, $column, 'cards.edit');
            $card = $this->cards->find($owner, $id, $column);
            if ($base !== null) {
                $this->assertUnchanged($card, $base);
            }
            $this->validateDates($card, $attributes);
            $this->syncAssignees($card, $board, $attributes['assignee_ids'] ?? null);
            unset($attributes['assignee_ids']);
            $this->cards->update($card, $attributes);
            BoardChanged::dispatch($this->boards->recipients($board), $board->id);

            return $transaction->commit($card);
        });
    }

    public function delete(int $owner, int $column, int $id): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $column, $id) {
            $board = $this->lockColumn($owner, $column, 'cards.delete');
            $this->cards->delete($this->cards->find($owner, $id, $column));
            BoardChanged::dispatch($this->boards->recipients($board), $board->id);

            return $transaction->commit();
        });
    }

    public function move(int $owner, int $id, int $column, ?int $previousId, ?int $nextId): Card
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $id, $column, $previousId, $nextId) {
            $board = $this->cards->boardId($owner, $id);
            $locked = $this->boards->lock($owner, $board, 'cards.move');
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

    private function lockColumn(int $owner, int $column, string $permission): Board
    {
        $board = $this->boards->lock($owner, $this->columns->find($owner, $column)->board_id, $permission);
        $this->columns->find($owner, $column, $board->id);

        return $board;
    }

    private function validateDates(Card $card, array $attributes): void
    {
        $dates = $attributes + [
            'start_date' => $card->start_date?->toDateString(),
            'due_date' => $card->due_date?->toDateString(),
        ];

        if ($dates['start_date'] !== null && $dates['due_date'] !== null && $dates['start_date'] > $dates['due_date']) {
            throw ValidationException::withMessages(['due_date' => 'The due date must be on or after the start date.']);
        }
    }

    private function assertUnchanged(Card $card, array $base): void
    {
        $current = array_intersect_key($card->toArray(), $base);
        if (isset($base['assignee_ids'])) {
            sort($base['assignee_ids']);
            sort($current['assignee_ids']);
        }

        if ($this->sortObjectKeys($base) !== $this->sortObjectKeys($current)) {
            throw new ConflictHttpException('Some card fields have changed. Refresh the card before saving again.');
        }
    }

    private function sortObjectKeys(array $values): array
    {
        if (! array_is_list($values)) {
            ksort($values);
        }

        return array_map(fn (mixed $value): mixed => is_array($value) ? $this->sortObjectKeys($value) : $value, $values);
    }

    /** @param list<int>|null $assignees */
    private function syncAssignees(Card $card, Board $board, ?array $assignees): void
    {
        if ($assignees === null) {
            return;
        }

        if (array_diff($assignees, $this->boards->recipients($board)) !== []) {
            throw ValidationException::withMessages(['assignee_ids' => 'Choose members of this board.']);
        }

        $card->assignees()->sync($assignees);
        $card->load('assignees:users.id');
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
