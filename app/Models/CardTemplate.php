<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CardTemplate extends Model
{
    protected $fillable = ['board_id', 'name', 'title', 'description', 'labels', 'checklists'];

    protected $visible = ['id', 'name', 'title', 'description'];

    protected $casts = ['labels' => 'array', 'checklists' => 'array'];
}
