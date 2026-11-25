<?php

namespace App\Modules\Mail\Providers;

use Illuminate\Support\ServiceProvider;

final class MailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(resource_path('views/modules/mail'), 'bankmail');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
