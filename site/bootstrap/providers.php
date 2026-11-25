<?php

use App\Modules\Administration\Providers\AdministrationServiceProvider;
use App\Modules\Calculations\Providers\CalculationsServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\Mail\Providers\MailServiceProvider;
use App\Modules\Maintenance\Providers\MaintenanceServiceProvider;
use App\Modules\Monitoring\Providers\MonitoringServiceProvider;
use App\Modules\Reports\Providers\ReportsServiceProvider;
use App\Modules\Site\Providers\SiteServiceProvider;
use App\Providers\AppServiceProvider;

return [
    MaintenanceServiceProvider::class,
    MonitoringServiceProvider::class,
    MailServiceProvider::class,
    ReportsServiceProvider::class,
    AdministrationServiceProvider::class,
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    CalculationsServiceProvider::class,
    SiteServiceProvider::class,
];
