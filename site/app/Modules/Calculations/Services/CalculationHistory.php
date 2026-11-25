<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Services;

use App\Modules\Calculations\Models\Calculation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

final class CalculationHistory
{
    public function listing(): LengthAwarePaginator
    {
        return Calculation::query()->select(['public_id', 'product_name', 'created_at'])->latest('id')->paginate(20);
    }

    public function snapshot(string $publicId): array
    {
        return Calculation::query()->where('public_id', $publicId)->firstOrFail()->payload;
    }

    public function snapshots(array $publicIds): LazyCollection
    {
        return Calculation::query()->whereIn('public_id', $publicIds)->orderBy('id')->cursor()->map(fn (Calculation $record): array => ['id' => $record->public_id, 'payload' => $record->payload]);
    }
}
