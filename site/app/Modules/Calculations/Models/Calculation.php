<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Models;

use Illuminate\Database\Eloquent\Model;

final class Calculation extends Model
{
    protected $fillable = ['public_id', 'calculator_id', 'product_code', 'product_name', 'session_hash', 'payload'];

    protected $hidden = ['session_hash'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
