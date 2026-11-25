<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

final class Calculator extends Model
{
    protected $fillable = ['name', 'type', 'subtitle', 'rate', 'parameters'];

    protected function casts(): array
    {
        return ['parameters' => 'array', 'is_public' => 'boolean'];
    }
}
