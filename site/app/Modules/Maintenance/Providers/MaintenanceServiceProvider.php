<?php

namespace App\Modules\Maintenance\Providers;

use App\Modules\Maintenance\Console\Commands\CreateBackup;
use App\Modules\Maintenance\Console\Commands\CreateRelease;
use App\Modules\Maintenance\Console\Commands\VerifyBackup;
use Illuminate\Support\ServiceProvider;

final class MaintenanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([CreateBackup::class, VerifyBackup::class, CreateRelease::class]);
    }
}
