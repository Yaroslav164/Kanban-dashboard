<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Repositories\BoardRepository;
use App\Repositories\CardRepository;
use App\Repositories\ColumnRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CardArchiveService
{
    public function __construct(
        private BoardRepository $boards,
        private CardRepository $cards,
        private ColumnRepository $columns,
        private CardService $ordering,
        private UnitOfWork $transactions,
    ) {}

    /** @return list<array{id: int, title: string, column_id: int, column_title: string}> */
    public function all(int $actor, int $board): array
    {
        $this->boards->requireAccessible($actor, $board);

        return $this->cards->archivedOnBoard($actor, $board);
    }

    public function archive(int $actor, int $id): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($actor, $id) {
            $boardId = $this->cards->findIncludingArchived($actor, $id)->column->board_id;
            $board = $this->boards->lock($actor, $boardId, 'cards.delete');
            $card = $this->cards->findIncludingArchived($actor, $id);
            if ($card->archived_at === null) {
                $this->cards->archive($card);
                BoardChanged::dispatch($this->boards->recipients($board), $board->id);
            }

            return $transaction->commit();
        });
    }

    public function restore(int $actor, int $id, ?int $destination): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($actor, $id, $destination) {
            $boardId = $this->cards->findIncludingArchived($actor, $id)->column->board_id;
            $board = $this->boards->lock($actor, $boardId, 'cards.delete');
            $card = $this->cards->findIncludingArchived($actor, $id);
            $originalDeleted = $card->column->trashed();
            if ($originalDeleted && $destination === null) {
                throw new ConflictHttpException('The original column was deleted. Choose an active column.');
            }
            if (! $originalDeleted && $destination !== null && $destination !== $card->column_id) {
                throw new ConflictHttpException('Restore the card to its original column.');
            }
            if ($card->archived_at === null) {
                return $transaction->commit();
            }

            $target = $this->columns->find($actor, $destination ?? $card->column_id, $board->id);
            $this->cards->restore($card, $target->id, $this->ordering->appendPosition($target->id));
            BoardChanged::dispatch($this->boards->recipients($board), $board->id);

            return $transaction->commit();
        });
    }
}
