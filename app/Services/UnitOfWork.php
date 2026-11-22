<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

final class UnitOfWork
{
    private bool $confirmed = false;

    public function run(Closure $command): mixed
    {
        return DB::transaction(function () use ($command) {
            $transaction = new self;
            $result = $command($transaction);
            if (! $transaction->confirmed) {
                throw new LogicException('Service did not confirm its transaction.');
            }

            return $result;
        });
    }

    public function commit(mixed $result = null): mixed
    {
        $this->confirmed = true;

        return $result;
    }

    public function rollback(Throwable $error): never
    {
        throw $error;
    }
}
