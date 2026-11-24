<?php

declare(strict_types=1);

namespace App\Models;

enum BoardRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public const PERMISSIONS = [
        'board.rename', 'columns.create', 'columns.edit', 'columns.delete',
        'cards.create', 'cards.edit', 'cards.move', 'cards.delete', 'cards.comment', 'cards.attach',
    ];
}
