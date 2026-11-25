<?php

namespace App\Modules\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;

final class TechnicalEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['route_name', 'method', 'status', 'duration_ms', 'verification', 'created_at'];

    protected function casts(): array
    {
        return ['status' => 'integer', 'duration_ms' => 'float', 'verification' => 'boolean', 'created_at' => 'datetime'];
    }
}
