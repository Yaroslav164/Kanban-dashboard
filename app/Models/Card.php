<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Card extends Model
{
    use SoftDeletes;

    public const ACTIVE_SCOPE = 'unarchived';

    protected $fillable = [
        'column_id', 'title', 'description', 'position', 'start_date', 'due_date', 'completed', 'labels', 'checklists',
    ];

    protected $visible = [
        'id', 'column_id', 'title', 'description', 'position', 'created_at', 'updated_at',
        'start_date', 'due_date', 'completed', 'labels', 'checklists', 'assignee_ids',
    ];

    protected $attributes = [
        'description' => null,
        'start_date' => null,
        'due_date' => null,
        'completed' => false,
        'labels' => '[]',
        'checklists' => '[]',
    ];

    protected $casts = [
        'column_id' => 'integer',
        'position' => 'double',
        'start_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'completed' => 'boolean',
        'labels' => 'array',
        'checklists' => 'array',
        'archived_at' => 'datetime',
    ];

    protected $with = ['assignees:users.id'];

    protected $appends = ['assignee_ids'];

    protected static function booted(): void
    {
        self::addGlobalScope(self::ACTIVE_SCOPE, function (Builder $cards): void {
            $cards->whereNull('cards.archived_at');
        });
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'card_assignees')->orderBy('users.id');
    }

    public function getAssigneeIdsAttribute(): array
    {
        return $this->assignees->modelKeys();
    }
}
