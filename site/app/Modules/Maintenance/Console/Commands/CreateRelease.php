<?php

namespace App\Modules\Maintenance\Console\Commands;

use App\Modules\Maintenance\Services\ReleasePackage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CreateRelease extends Command
{
    protected $signature = 'manobank:package';

    protected $description = 'Создать пакет размещения без рабочей БД и секретов';

    public function handle(ReleasePackage $package): int
    {
        if (! is_file(public_path('build/manifest.json'))) {
            $this->error('Сначала выполните npm run build.');

            return self::FAILURE;
        }
        $directory = base_path('../docs/releases');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/МаноБанк_сайт.zip';
        file_put_contents($path, $package->bytes());
        $this->line($path);

        return self::SUCCESS;
    }
}
