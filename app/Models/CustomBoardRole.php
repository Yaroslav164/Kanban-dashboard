<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CustomBoardRole extends Model
{
    protected $table = 'board_roles';

    public $timestamps = false;

    protected $fillable = ['name', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    protected $visible = ['id', 'name', 'permissions', 'key'];

    protected $appends = ['key'];

    public function getKeyAttribute(): string
    {
        return 'custom-'.$this->id;
    }
}
