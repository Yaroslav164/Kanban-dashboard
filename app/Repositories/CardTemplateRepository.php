<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CardTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class CardTemplateRepository
{
    /** @return Collection<int, CardTemplate> */
    public function all(int $board): Collection
    {
        return $this->onBoard($board)->orderBy('id')->get(['id', 'name', 'title', 'description']);
    }

    public function find(int $board, int $id): CardTemplate
    {
        return $this->onBoard($board)->findOrFail($id);
    }

    public function create(int $board, string $name, array $attributes): CardTemplate
    {
        return CardTemplate::query()->create(['board_id' => $board, 'name' => $name] + $attributes);
    }

    /** @return Builder<CardTemplate> */
    private function onBoard(int $board): Builder
    {
        return CardTemplate::query()->where('board_id', $board);
    }
}
