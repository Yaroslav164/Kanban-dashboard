<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\Column;
use App\Repositories\BoardRepository;
use App\Repositories\ColumnRepository;
use Illuminate\Database\Eloquent\Collection;

final class ColumnService
{
    public function __construct(
        private BoardRepository $boards,
        private ColumnRepository $columns,
        private UnitOfWork $transactions,
    ) {}

    public function all(int $owner, int $board): Collection
    {
        $this->boards->requireAccessible($owner, $board);

        return $this->columns->all($owner, $board);
    }

    public function find(int $owner, int $board, int $id): Column
    {
        return $this->columns->find($owner, $id, $board);
    }

    public function create(int $owner, int $board, string $title): Column
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $board, $title) {
            $locked = $this->boards->lock($owner, $board, 'columns.create');
            $column = $this->columns->create($board, $title);
            BoardChanged::dispatch($this->boards->recipients($locked), $board);

            return $transaction->commit($column);
        });
    }

    public function rename(int $owner, int $board, int $id, string $title): Column
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $board, $id, $title) {
            $locked = $this->boards->lock($owner, $board, 'columns.edit');
            $column = $this->columns->rename($this->columns->find($owner, $id, $board), $title);
            BoardChanged::dispatch($this->boards->recipients($locked), $board);

            return $transaction->commit($column);
        });
    }

    public function delete(int $owner, int $board, int $id): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($owner, $board, $id) {
            $locked = $this->boards->lock($owner, $board, 'columns.delete');
            $this->columns->delete($this->columns->find($owner, $id, $board));
            BoardChanged::dispatch($this->boards->recipients($locked), $board);

            return $transaction->commit();
        });
    }
}
