<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Card;
use App\Repositories\CardHistoryRepository;
use App\Repositories\CardRepository;
use App\Repositories\InboxRepository;

final class CardTrackingService
{
    public function __construct(
        private CardRepository $cards,
        private CardHistoryRepository $history,
        private InboxRepository $inbox,
    ) {}

    public function history(int $actor, int $card): array
    {
        $this->cards->find($actor, $card);

        return $this->history->all($card);
    }

    public function update(int $actor, Card $card, array $previousAssignees): void
    {
        foreach (['start_date', 'due_date'] as $field) {
            if ($card->wasChanged($field)) {
                $this->history->add($card->id, $actor, $field, $card->$field?->toDateString());
            }
        }
        if ($card->wasChanged('completed')) {
            $this->history->add($card->id, $actor, $card->completed ? 'completed' : 'reopened');
        }
        $this->assignments($actor, $card, $previousAssignees);
    }

    public function assignments(int $actor, Card $card, array $previous): void
    {
        $current = $card->assignee_ids;
        if ($previous === $current) {
            return;
        }

        $event = $this->history->add($card->id, $actor, 'assignees');
        foreach (array_diff($current, $previous, [$actor]) as $assignee) {
            $this->inbox->add($assignee, $card->id, 'assigned', (string) $event);
        }
    }

    public function moved(int $actor, Card $card, string $column): void
    {
        if ($card->wasChanged(['column_id', 'position'])) {
            $this->history->add($card->id, $actor, 'moved', $column);
        }
    }

    public function mentions(int $actor, int $card, int $comment, string $body, array $members): void
    {
        preg_match_all('/(?<![\pL\pN@])@([^\s<>,;!?()]+)/u', $body, $matches);
        $emails = array_unique(array_map(fn (string $email): string => mb_strtolower(rtrim($email, '.')), $matches[1]));
        if ($emails === []) {
            return;
        }

        foreach ($this->inbox->mentionRecipients(array_diff($members, [$actor]), $emails) as $user) {
            $this->inbox->add($user, $card, 'mentioned', (string) $comment);
        }
    }
}
