<?php

namespace App\Modules\Site\Providers;

use Illuminate\Support\ServiceProvider;

final class SiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(resource_path('views/modules/site'), 'site');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
