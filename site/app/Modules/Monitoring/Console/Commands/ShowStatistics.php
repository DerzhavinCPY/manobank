<?php

namespace App\Modules\Monitoring\Console\Commands;

use App\Modules\Monitoring\Services\Statistics;
use Illuminate\Console\Command;

final class ShowStatistics extends Command
{
    protected $signature = 'manobank:statistics {--verification : Только проверочные запросы}';

    protected $description = 'Показать фактическую статистику технических запросов';

    public function handle(Statistics $statistics): int
    {
        $this->line(json_encode($statistics->summary((bool) $this->option('verification')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
