<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\BoardRepository;
use App\Repositories\CardActivityRepository;
use App\Repositories\CardRepository;
use App\Repositories\ColumnRepository;
use Illuminate\Support\Str;

final class DemoBoardService
{
    public function __construct(
        private BoardRepository $boards,
        private ColumnRepository $columns,
        private CardRepository $cards,
        private CardActivityRepository $activity,
    ) {}

    public function createFor(User $user): void
    {
        $board = $this->boards->create($user->id, 'Демо-доска');
        $todo = $this->columns->create($board->id, 'К выполнению');
        $doing = $this->columns->create($board->id, 'В работе');
        $done = $this->columns->create($board->id, 'Готово');
        $today = today();

        $example = $this->cards->create($todo->id, [
            'title' => 'Подготовить демо проекта',
            'description' => 'Раскройте блоки карточки, чтобы посмотреть метки, даты, исполнителя, чеклист и вложение. Перетащите карточку в другую колонку или откройте её в режиме «Дела» или «Календарь».',
            'labels' => [['name' => 'Пример', 'color' => '#5268c1']],
            'start_date' => $today->toDateString(),
            'due_date' => $today->copy()->addDays(3)->toDateString(),
            'checklists' => [[
                'id' => (string) Str::uuid(),
                'title' => 'Перед показом',
                'items' => [
                    ['id' => (string) Str::uuid(), 'title' => 'Создать первую карточку', 'completed' => true],
                    ['id' => (string) Str::uuid(), 'title' => 'Проверить календарь', 'completed' => false],
                ],
            ]],
        ], 1024);
        $example->assignees()->attach($user->id);
        $this->activity->addComment($example->id, $user->id, 'Пример комментария. Здесь команда может обсуждать задачу.');
        $this->activity->addAttachment($example->id, 'План показа.txt', 'text/plain', "1. Открыть карточку.\n2. Отметить пункт чеклиста.\n3. Проверить срок в календаре.\n");

        $this->cards->create($doing->id, [
            'title' => 'Посмотреть режимы доски',
            'description' => 'Переключитесь между доской, списком, делами и календарём.',
        ], 1024);
        $this->cards->create($done->id, [
            'title' => 'Начать работу с доской',
            'completed' => true,
        ], 1024);

        $board->customRoles()->create(['name' => 'Комментатор', 'permissions' => ['cards.comment']]);
    }
}
