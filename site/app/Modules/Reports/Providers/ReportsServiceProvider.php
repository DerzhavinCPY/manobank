<?php

namespace App\Modules\Reports\Providers;

use Illuminate\Support\ServiceProvider;

final class ReportsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(resource_path('views/modules/reports'), 'reports');
    }
}
