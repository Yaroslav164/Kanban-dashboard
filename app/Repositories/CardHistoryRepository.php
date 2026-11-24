<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

final class CardHistoryRepository
{
    public function add(int $card, int $actor, string $kind, ?string $detail = null): int
    {
        return (int) DB::table('card_history')->insertGetId([
            'card_id' => $card,
            'actor_id' => $actor,
            'kind' => $kind,
            'detail' => $detail,
            'created_at' => now(),
        ]);
    }

    public function all(int $card): array
    {
        return DB::table('card_history')->leftJoin('users', 'users.id', '=', 'card_history.actor_id')
            ->where('card_history.card_id', $card)->orderByDesc('card_history.id')->limit(100)
            ->get(['card_history.*', 'users.name as actor_name'])
            ->map(fn (stdClass $row): array => [
                'id' => (int) $row->id,
                'kind' => $row->kind,
                'detail' => $row->detail,
                'actor_name' => $row->actor_name,
                'created_at' => Carbon::parse($row->created_at)->toISOString(),
            ])->all();
    }
}
