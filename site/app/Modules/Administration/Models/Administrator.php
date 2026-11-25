<?php

declare(strict_types=1);

namespace App\Modules\Administration\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class Administrator extends Authenticatable
{
    protected $fillable = ['email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
