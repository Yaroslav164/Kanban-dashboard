<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Board;
use App\Models\BoardRole;
use App\Models\Card;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

final class BoardRepository
{
    public function all(int $user): Collection
    {
        return $this->withRole($user)->orderBy('boards.id')->get();
    }

    public function find(int $user, int $id): Board
    {
        return $this->withRole($user)->with('columns.cards')->findOrFail($id);
    }

    public function requireAccessible(int $user, int $id): Board
    {
        return Board::query()->accessibleTo($user)->findOrFail($id);
    }

    public function lock(int $user, int $id, BoardRole|string $permission): Board
    {
        Board::query()->accessibleTo($user)->lockForUpdate()->findOrFail($id);

        // A queued mutation must observe membership and permissions after obtaining the lock.
        $board = $this->withRole($user)->findOrFail($id);
        if ($permission === BoardRole::Owner) {
            abort_unless($board->role === 'owner', 403);
        } else {
            abort_unless(in_array($permission, $board->permissions, true), 403);
        }

        return $board;
    }

    public function create(int $owner, string $title): Board
    {
        $board = Board::query()->create(['user_id' => $owner, 'title' => $title]);

        return $this->withRole($owner)->findOrFail($board->id);
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
        return Board::query()->accessibleTo($user)
            ->leftJoin('board_members as membership', function (JoinClause $join) use ($user) {
                $join->on('membership.board_id', '=', 'boards.id')->where('membership.user_id', $user);
            })
            ->leftJoin('board_roles as custom', function (JoinClause $join) {
                $join->on('custom.id', '=', 'membership.custom_role_id')->on('custom.board_id', '=', 'boards.id');
            })
            ->select('boards.*')
            ->selectRaw("case when boards.user_id = ? then 'owner' when custom.id is not null then concat('custom-', custom.id) else membership.role end as role", [$user])
            ->selectRaw("case when boards.user_id = ? then ? when custom.id is not null then cast(custom.permissions as text) when membership.role = 'editor' then ? else '[]' end as permissions", [
                $user, json_encode(BoardRole::PERMISSIONS), json_encode(BoardRole::PERMISSIONS),
            ])
            ->withCasts(['permissions' => 'array']);
    }
}
