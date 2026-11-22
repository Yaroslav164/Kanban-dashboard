<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'github_id'];

    protected $visible = ['id', 'name', 'email'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
