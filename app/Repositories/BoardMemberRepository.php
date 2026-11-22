<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Board;
use App\Models\BoardRole;
use App\Models\User;

final class BoardMemberRepository
{
    /** @return list<array{id: int, name: string, email: string, role: string}> */
    public function all(Board $board): array
    {
        return [
            $this->data($board->owner()->firstOrFail(), BoardRole::Owner),
            ...$board->members()->orderBy('users.id')->get()
                ->map(fn (User $member) => $this->data($member, BoardRole::from($member->pivot->role)))
                ->all(),
        ];
    }

    public function contains(Board $board, int $user): bool
    {
        return $board->user_id === $user || $board->members()->whereKey($user)->exists();
    }

    public function find(Board $board, int $user): User
    {
        return $board->members()->findOrFail($user);
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    public function add(Board $board, User $user, BoardRole $role): array
    {
        $board->members()->attach($user->id, ['role' => $role->value]);

        return $this->data($user, $role);
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    public function update(Board $board, User $user, BoardRole $role): array
    {
        $board->members()->updateExistingPivot($user->id, ['role' => $role->value]);

        return $this->data($user, $role);
    }

    public function remove(Board $board, User $user): void
    {
        $board->members()->detach($user->id);
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    private function data(User $user, BoardRole $role): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $role->value];
    }
}
