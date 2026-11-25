<?php

namespace App\Modules\Administration\Providers;

use App\Modules\Administration\Console\Commands\SetAdministratorPassword;
use Illuminate\Support\ServiceProvider;

final class AdministrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(resource_path('views/modules/administration'), 'administration');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->commands([SetAdministratorPassword::class]);
    }
}
