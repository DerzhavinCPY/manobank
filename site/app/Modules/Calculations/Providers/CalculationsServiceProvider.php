<?php

namespace App\Modules\Calculations\Providers;

use Illuminate\Support\ServiceProvider;

final class CalculationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
