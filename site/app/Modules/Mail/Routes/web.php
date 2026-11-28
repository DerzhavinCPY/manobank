<?php

use App\Modules\Mail\Http\Controllers\CalculationMailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:5,1,calculation-email:'])->post('/calculations/{id}/email', CalculationMailController::class)->whereUuid('id')->name('mail.calculation');
