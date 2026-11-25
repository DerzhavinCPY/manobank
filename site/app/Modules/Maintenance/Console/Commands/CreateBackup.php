<?php

namespace App\Modules\Maintenance\Console\Commands;

use App\Modules\Maintenance\Services\ProjectBackup;
use Illuminate\Console\Command;

final class CreateBackup extends Command
{
    protected $signature = 'manobank:backup';

    protected $description = 'Создать локальную резервную копию исходников, сборки, изображений и SQLite';

    public function handle(ProjectBackup $backup): int
    {
        $this->line($backup->create());

        return self::SUCCESS;
    }
}
