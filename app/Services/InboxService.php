<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InboxRepository;

final class InboxService
{
    public function __construct(private InboxRepository $inbox, private UnitOfWork $transactions) {}

    public function all(int $user): array
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($user) {
            $today = today();
            foreach ($this->inbox->upcoming($user, $today->toDateString(), $today->copy()->addDay()->toDateString()) as $card) {
                $this->inbox->add($user, $card->id, 'due_soon', $card->due_date->toDateString());
            }

            return $transaction->commit($this->inbox->all($user));
        });
    }

    public function read(int $user, int $notification): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($user, $notification) {
            $this->inbox->read($user, $notification);

            return $transaction->commit();
        });
    }
}
