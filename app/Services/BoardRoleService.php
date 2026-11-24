<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\BoardRole;
use App\Models\CustomBoardRole;
use App\Repositories\BoardRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class BoardRoleService
{
    public function __construct(private BoardRepository $boards, private UnitOfWork $transactions) {}

    public function all(int $user, int $boardId): Collection
    {
        return $this->boards->requireAccessible($user, $boardId)->customRoles()->orderBy('name')->get();
    }

    public function save(int $user, int $boardId, ?int $id, array $attributes): CustomBoardRole
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($user, $boardId, $id, $attributes) {
            $board = $this->boards->lock($user, $boardId, BoardRole::Owner);
            $role = $id === null ? $board->customRoles()->make() : $board->customRoles()->findOrFail($id);

            $duplicate = $board->customRoles()->where('name', $attributes['name']);
            if ($id !== null) {
                $duplicate->where('id', '!=', $id);
            }
            if ($duplicate->exists()) {
                throw ValidationException::withMessages(['name' => 'A role with this name already exists.']);
            }

            $role->fill($attributes)->save();
            BoardChanged::dispatch($this->boards->recipients($board), $boardId);

            return $transaction->commit($role);
        });
    }

    public function remove(int $user, int $boardId, int $id): void
    {
        $this->transactions->run(function (UnitOfWork $transaction) use ($user, $boardId, $id) {
            $board = $this->boards->lock($user, $boardId, BoardRole::Owner);
            $role = $board->customRoles()->findOrFail($id);
            if ($board->members()->wherePivot('custom_role_id', $id)->exists()) {
                throw ValidationException::withMessages(['role' => 'Сначала назначьте участникам этой роли другую роль.']);
            }
            $role->delete();
            BoardChanged::dispatch($this->boards->recipients($board), $boardId);

            return $transaction->commit();
        });
    }
}
