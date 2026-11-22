<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Board;
use App\Models\BoardRole;
use App\Models\Card;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class BoardRepository
{
    public function all(int $user): Collection
    {
        return $this->withRole($user)->orderBy('id')->get();
    }

    public function find(int $user, int $id): Board
    {
        return $this->withRole($user)->with('columns.cards')->findOrFail($id);
    }

    public function requireAccessible(int $user, int $id): Board
    {
        return Board::query()->accessibleTo($user)->findOrFail($id);
    }

    public function lock(int $user, int $id, BoardRole $requiredRole = BoardRole::Editor): Board
    {
        $board = Board::query()->accessibleTo($user)->lockForUpdate()->findOrFail($id);

        // Re-read membership after the board lock serializes this write with role changes.
        $role = $board->user_id === $user
            ? BoardRole::Owner->value
            : $board->members()->whereKey($user)->value('board_members.role');
        abort_if($role === null, 404, 'Resource not found.');
        abort_if($role !== BoardRole::Owner->value && $role !== $requiredRole->value, 403);

        return $board->setAttribute('role', $role)->syncOriginalAttribute('role');
    }

    public function create(int $owner, string $title): Board
    {
        return Board::query()->create(['user_id' => $owner, 'title' => $title])
            ->setAttribute('role', BoardRole::Owner->value)
            ->syncOriginalAttribute('role');
    }

    public function rename(Board $board, string $title): Board
    {
        $board->update(['title' => $title]);

        return $board;
    }

    public function delete(Board $board): void
    {
        $columns = $board->columns()->getQuery()->reorder();
        Card::query()->whereIn('column_id', (clone $columns)->select('id'))->delete();
        $columns->delete();
        $board->delete();
    }

    /** @return list<int> */
    public function recipients(Board $board): array
    {
        return [$board->user_id, ...$board->members()->orderBy('users.id')->pluck('users.id')->all()];
    }

    private function withRole(int $user): Builder
    {
        $membership = DB::table('board_members')
            ->select('role')
            ->whereColumn('board_id', 'boards.id')
            ->where('user_id', $user);

        return Board::query()->accessibleTo($user)
            ->select('boards.*')
            ->selectRaw("case when boards.user_id = ? then 'owner' else ({$membership->toSql()}) end as role", [
                $user,
                ...$membership->getBindings(),
            ]);
    }
}
