<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\BoardChanged;
use App\Models\CardTemplate;
use App\Repositories\BoardRepository;
use App\Repositories\CardRepository;
use App\Repositories\CardTemplateRepository;
use Illuminate\Database\Eloquent\Collection;

final class CardTemplateService
{
    public function __construct(
        private BoardRepository $boards,
        private CardRepository $cards,
        private CardTemplateRepository $templates,
        private UnitOfWork $transactions,
    ) {}

    /** @return Collection<int, CardTemplate> */
    public function all(int $actor, int $board): Collection
    {
        $this->boards->requireAccessible($actor, $board);

        return $this->templates->all($board);
    }

    public function create(int $actor, int $board, string $name, int $source): CardTemplate
    {
        return $this->transactions->run(function (UnitOfWork $transaction) use ($actor, $board, $name, $source) {
            $locked = $this->boards->lock($actor, $board, 'cards.create');
            $card = $this->cards->findOnBoard($board, $source);
            $template = $this->templates->create($board, $name, [
                'title' => $card->title,
                'description' => $card->description,
                'labels' => $card->labels,
                'checklists' => $card->checklists,
            ]);
            BoardChanged::dispatch($this->boards->recipients($locked), $board);

            return $transaction->commit($template);
        });
    }
}
