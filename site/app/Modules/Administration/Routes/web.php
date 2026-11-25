<?php

use App\Modules\Administration\Http\Controllers\AdministrationController;
use App\Modules\Administration\Http\Controllers\AuthenticationController;
use App\Modules\Administration\Http\Controllers\ExportController;
use App\Modules\Administration\Http\Middleware\RequireAdministrator;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthenticationController::class, 'form'])->name('login');
    Route::post('/login', [AuthenticationController::class, 'login'])->name('login.store');
    Route::middleware(RequireAdministrator::class)->group(function (): void {
        Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');
        Route::get('/', [AdministrationController::class, 'dashboard'])->name('dashboard');
        Route::get('/calculators', [AdministrationController::class, 'calculators'])->name('calculators');
        Route::get('/calculators/create', [AdministrationController::class, 'create'])->name('calculators.create');
        Route::post('/calculators', [AdministrationController::class, 'store'])->name('calculators.store');
        Route::get('/calculators/{id}/edit', [AdministrationController::class, 'edit'])->whereNumber('id')->name('calculators.edit');
        Route::put('/calculators/{id}', [AdministrationController::class, 'update'])->whereNumber('id')->name('calculators.update');
        Route::delete('/calculators/{id}', [AdministrationController::class, 'destroy'])->whereNumber('id')->name('calculators.destroy');
        Route::get('/calculations', [AdministrationController::class, 'calculations'])->name('calculations');
        Route::get('/calculations/export', ExportController::class)->name('calculations.export');
        Route::get('/calculations/{id}', [AdministrationController::class, 'calculation'])->name('calculations.show');
    });
});
