<?php

use App\Modules\Site\Http\Controllers\AssetController;
use App\Modules\Site\Http\Controllers\SearchController;
use App\Modules\Site\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/robots.txt', [SearchController::class, 'robots'])->name('site.robots');
    Route::get('/sitemap.xml', [SearchController::class, 'sitemap'])->name('site.sitemap');
    Route::get('/', [SiteController::class, 'catalog'])->name('site.start');
    Route::get('/calculator', [SiteController::class, 'catalog'])->name('site.catalog');
    Route::get('/calculator/{product}', [SiteController::class, 'calculator'])->name('site.calculator');
    Route::get('/account', [SiteController::class, 'catalog'])->name('site.account');
    Route::get('/contacts', [SiteController::class, 'contacts'])->name('site.contacts');
    Route::get('/requisites', [SiteController::class, 'requisites'])->name('site.requisites');
    foreach (['home', 'products', 'support', 'privacy', 'terms', 'pension-program'] as $page) {
        Route::get('/'.$page, [SiteController::class, 'maintenance'])->name('site.'.$page);
    }
    Route::get('/media/{asset}', AssetController::class)->name('site.media');
});
