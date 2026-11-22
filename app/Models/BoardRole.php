<?php

declare(strict_types=1);

namespace App\Models;

enum BoardRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';
}
