<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

final class InboxRepository
{
    public function mentionRecipients(array $members, array $emails): array
    {
        return User::query()->whereKey($members)->whereIn(DB::raw('lower(email)'), $emails)->pluck('id')->all();
    }

    public function add(int $user, int $card, string $kind, string $source): void
    {
        DB::table('board_notifications')->insertOrIgnore([
            'user_id' => $user,
            'card_id' => $card,
            'kind' => $kind,
            'source' => $source,
            'created_at' => now(),
        ]);
    }

    public function upcoming(int $user, string $from, string $to): array
    {
        return $this->accessibleCards($user)->without('assignees')->where('completed', false)
            ->whereBetween('due_date', [$from, $to])->whereHas('assignees', fn (EloquentBuilder $users) => $users->whereKey($user))
            ->get(['cards.id', 'cards.due_date'])->all();
    }

    public function all(int $user): array
    {
        return $this->accessible($user)->join('columns', 'columns.id', '=', 'cards.column_id')
            ->orderByRaw('CASE WHEN board_notifications.read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('board_notifications.id')->limit(100)
            ->get(['board_notifications.*', 'columns.board_id', 'cards.title'])
            ->map(fn (stdClass $row): array => [
                'id' => (int) $row->id,
                'kind' => $row->kind,
                'board_id' => (int) $row->board_id,
                'card_id' => (int) $row->card_id,
                'card_title' => $row->title,
                'read' => $row->read_at !== null,
                'created_at' => Carbon::parse($row->created_at)->toISOString(),
            ])->all();
    }

    public function read(int $user, int $notification): void
    {
        abort_unless($this->accessible($user)->where('board_notifications.id', $notification)->exists(), 404);
        DB::table('board_notifications')->where('user_id', $user)->where('id', $notification)
            ->whereNull('read_at')->update(['read_at' => now()]);
    }

    private function accessible(int $user): Builder
    {
        return DB::table('board_notifications')->where('board_notifications.user_id', $user)
            ->whereIn('board_notifications.card_id', $this->accessibleCards($user)->select('cards.id')->toBase())
            ->join('cards', 'cards.id', '=', 'board_notifications.card_id')
            ->where(function (Builder $query) use ($user): void {
                $query->where('board_notifications.kind', '<>', 'due_soon')
                    ->orWhere(function (Builder $due) use ($user): void {
                        $due->where('cards.completed', false)
                            ->whereColumn(DB::raw('cast(cards.due_date as varchar)'), 'board_notifications.source')
                            ->whereExists(fn (Builder $assignees) => $assignees->selectRaw('1')->from('card_assignees')
                                ->whereColumn('card_assignees.card_id', 'cards.id')->where('card_assignees.user_id', $user));
                    });
            });
    }

    private function accessibleCards(int $user): EloquentBuilder
    {
        return Card::query()->whereHas('column.board', fn (EloquentBuilder $boards) => $boards->accessibleTo($user));
    }
}
