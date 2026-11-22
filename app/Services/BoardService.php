<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\Board;
use App\Repositories\BoardRepository;
use Illuminate\Database\Eloquent\Collection;

final class BoardService
{
    public function __construct(private BoardRepository $boards, private UnitOfWork $transactions) {}

    public function all(int $owner): Collection
    {
        return $this->boards->all($owner);
    }

    public function find(int $owner, int $id): Board
    {
        return $this->boards->find($owner, $id);
    }

    public function create(int $owner, string $title): Board
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $title) {
            $board = $this->boards->create($owner, $title);
            BoardChanged::dispatch($owner, $board->id);

            return $transaction->commit($board);
        });
    }

    public function rename(int $owner, int $id, string $title): Board
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $id, $title) {
            $board = $this->boards->rename($this->boards->lock($owner, $id), $title);
            BoardChanged::dispatch($owner, $id);

            return $transaction->commit($board);
        });
    }

    public function delete(int $owner, int $id): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $id) {
            $this->boards->delete($this->boards->lock($owner, $id));
            BoardChanged::dispatch($owner, $id);

            return $transaction->commit();
        });
    }
}
