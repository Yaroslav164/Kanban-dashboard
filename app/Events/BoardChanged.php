<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class BoardChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable;

    /** @param list<int> $recipients */
    public function __construct(private array $recipients, private int $boardId) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return array_map(fn (int $id) => new PrivateChannel("users.{$id}"), $this->recipients);
    }

    public function broadcastAs(): string
    {
        return 'board.changed';
    }

    public function broadcastWith(): array
    {
        return ['board_id' => $this->boardId];
    }
}
