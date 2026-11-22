<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Card extends Model
{
    use SoftDeletes;

    protected $fillable = ['column_id', 'title', 'description', 'position'];

    protected $visible = ['id', 'column_id', 'title', 'description', 'position', 'created_at', 'updated_at'];

    protected $attributes = ['description' => null];

    protected $casts = ['column_id' => 'integer', 'position' => 'double'];

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }
}
