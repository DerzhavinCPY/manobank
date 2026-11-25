<?php

namespace App\Modules\Monitoring\Providers;

use App\Modules\Monitoring\Console\Commands\ShowStatistics;
use Illuminate\Support\ServiceProvider;

final class MonitoringServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->commands([ShowStatistics::class]);
    }
}
