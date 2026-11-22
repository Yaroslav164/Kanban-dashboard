<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Board extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'title'];

    protected $visible = ['id', 'title', 'created_at', 'updated_at', 'columns', 'role'];

    public function scopeAccessibleTo(Builder $boards, int $user): Builder
    {
        return $boards->where(fn (Builder $accessible) => $accessible
            ->where('boards.user_id', $user)
            ->orWhereHas('members', fn (Builder $members) => $members->whereKey($user)));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_members')->withPivot('role');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position')->orderBy('id');
    }
}
