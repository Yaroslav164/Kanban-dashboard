<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\Board;
use App\Models\BoardRole;
use App\Repositories\BoardMemberRepository;
use App\Repositories\BoardRepository;
use App\Repositories\CardRepository;
use App\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;

final class BoardMemberService
{
    public function __construct(
        private BoardRepository $boards,
        private BoardMemberRepository $members,
        private UserRepository $users,
        private CardRepository $cards,
        private UnitOfWork $transactions,
    ) {}

    /** @return list<array{id: int, name: string, email: string, role: string}> */
    public function all(int $actor, int $boardId): array
    {
        return $this->members->all($this->boards->requireAccessible($actor, $boardId));
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    public function add(int $actor, int $boardId, string $email, string $role): array
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($actor, $boardId, $email, $role) {
            $board = $this->boards->lock($actor, $boardId, BoardRole::Owner);
            $user = $this->users->byEmail(mb_strtolower($email));
            if ($user === null) {
                throw ValidationException::withMessages(['email' => 'No account was found for this email.']);
            }
            if ($this->members->contains($board, $user->id)) {
                throw ValidationException::withMessages(['email' => 'This user already belongs to the board.']);
            }

            $member = $this->members->add($board, $user, $role);
            BoardChanged::dispatch($this->boards->recipients($board), $boardId);

            return $transaction->commit($member);
        });
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    public function changeRole(int $actor, int $boardId, int $memberId, string $role): array
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($actor, $boardId, $memberId, $role) {
            $board = $this->boards->lock($actor, $boardId, BoardRole::Owner);
            $this->requireEditableMember($board, $memberId);
            $member = $this->members->update($board, $this->members->find($board, $memberId), $role);
            BoardChanged::dispatch($this->boards->recipients($board), $boardId);

            return $transaction->commit($member);
        });
    }

    public function remove(int $actor, int $boardId, int $memberId): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($actor, $boardId, $memberId) {
            $board = $this->boards->lock($actor, $boardId, BoardRole::Owner);
            $this->requireEditableMember($board, $memberId);
            $this->members->remove($board, $this->members->find($board, $memberId));
            $this->cards->removeAssignee($boardId, $memberId);
            BoardChanged::dispatch([...$this->boards->recipients($board), $memberId], $boardId);

            return $transaction->commit();
        });
    }

    private function requireEditableMember(Board $board, int $memberId): void
    {
        if ($board->user_id === $memberId) {
            throw ValidationException::withMessages(['member' => 'The board owner cannot be changed.']);
        }
    }
}
