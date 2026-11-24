<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Board;
use App\Models\BoardRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class BoardMemberRepository
{
    /** @return list<array{id: int, name: string, email: string, role: string}> */
    public function all(Board $board): array
    {
        return [
            $this->data($board->owner()->firstOrFail(), BoardRole::Owner->value),
            ...$board->members()->orderBy('users.id')->get()
                ->map(fn (User $member) => $this->data($member, $member->pivot->custom_role_id ? 'custom-'.$member->pivot->custom_role_id : $member->pivot->role))
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
    public function add(Board $board, User $user, string $role): array
    {
        $board->members()->attach($user->id, $this->assignment($board, $role));

        return $this->data($user, $role);
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    public function update(Board $board, User $user, string $role): array
    {
        $board->members()->updateExistingPivot($user->id, $this->assignment($board, $role));

        return $this->data($user, $role);
    }

    private function assignment(Board $board, string $role): array
    {
        if (in_array($role, ['editor', 'viewer'], true)) {
            return ['role' => $role, 'custom_role_id' => null];
        }
        if (preg_match('/^custom-([1-9][0-9]{0,17})$/D', $role, $match) && $board->customRoles()->whereKey($match[1])->exists()) {
            return ['role' => 'viewer', 'custom_role_id' => (int) $match[1]];
        }

        throw ValidationException::withMessages(['role' => 'Choose a role from this board.']);
    }

    public function remove(Board $board, User $user): void
    {
        $board->members()->detach($user->id);
    }

    /** @return array{id: int, name: string, email: string, role: string} */
    private function data(User $user, string $role): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $role];
    }
}
