<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Column extends Model
{
    use SoftDeletes;

    protected $fillable = ['board_id', 'title', 'position'];

    protected $visible = ['id', 'board_id', 'title', 'position', 'created_at', 'updated_at', 'cards'];

    protected $casts = ['board_id' => 'integer', 'position' => 'integer'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('position')->orderBy('id');
    }
}
