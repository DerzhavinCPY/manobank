<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Http\Controllers;

use App\Modules\Calculations\Http\Requests\CalculationRequest;
use App\Modules\Calculations\Services\CalculationService;
use App\Modules\Calculations\Services\ResultPresenter;
use App\Modules\Catalog\Services\ProductCatalog;
use Illuminate\Http\JsonResponse;

final class CalculationController
{
    public function __invoke(CalculationRequest $request, string $product, ProductCatalog $catalog, CalculationService $service, ResultPresenter $presenter): JsonResponse
    {
        $record = $service->create($catalog->publicProduct($product), $request->validated(), $request->session()->getId());

        return response()->json(['id' => $record->public_id] + $presenter->present($record->payload), 201);
    }
}
