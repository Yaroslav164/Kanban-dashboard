<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Board extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'title'];

    protected $visible = ['id', 'title', 'created_at', 'updated_at', 'columns'];

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position')->orderBy('id');
    }
}
