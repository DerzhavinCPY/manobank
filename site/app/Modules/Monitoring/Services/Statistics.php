<?php

namespace App\Modules\Monitoring\Services;

use App\Modules\Monitoring\Models\TechnicalEvent;

final class Statistics
{
    public function summary(bool $verification = false): array
    {
        $query = TechnicalEvent::query()->where('verification', $verification);

        return [
            'verification' => $verification,
            'first_request' => (clone $query)->min('created_at'), 'last_request' => (clone $query)->max('created_at'),
            'requests' => (clone $query)->count(), 'errors' => (clone $query)->where('status', '>=', 400)->count(),
            'average_ms' => round((float) (clone $query)->avg('duration_ms'), 2),
            'routes' => (clone $query)->selectRaw('route_name, count(*) as requests, avg(duration_ms) as average_ms')->groupBy('route_name')->get()->toArray(),
        ];
    }
}
