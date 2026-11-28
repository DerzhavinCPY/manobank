<?php

use App\Modules\Calculations\Http\Controllers\CalculationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:30,1,calculations:'])->group(function (): void {
    Route::post('/calculations/{product}', CalculationController::class)->name('calculations.store');
});
