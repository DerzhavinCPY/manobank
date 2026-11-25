<?php

namespace App\Modules\Maintenance\Console\Commands;

use App\Modules\Maintenance\Services\ProjectBackup;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class VerifyBackup extends Command
{
    protected $signature = 'manobank:verify-backup {archive}';

    protected $description = 'Проверить восстановление исходников и SQLite в изолированной копии';

    public function handle(ProjectBackup $backup): int
    {
        $copy = $backup->extractVerified($this->argument('archive'));
        try {
            if (! hash_equals(hash_file('sha256', base_path('composer.lock')), hash_file('sha256', $copy.'/site/composer.lock'))) {
                $this->error('Нужны зависимости, соответствующие composer.lock из резервной копии.');

                return self::FAILURE;
            }
            $process = new Process([PHP_BINARY, 'site/app/Modules/Maintenance/Verification/restore.php', '../../../site/vendor'], $copy, [
                'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
                'APP_URL' => 'http://localhost', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $copy.'/site/database/database.sqlite',
                'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'LOG_CHANNEL' => 'stderr', 'MANOBANK_MAIL_ENABLED' => 'false',
            ]);
            $process->setTimeout(120)->run();
            $this->line($process->getOutput());
            if (! $process->isSuccessful()) {
                $this->error($process->getErrorOutput());
            }

            return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
        } finally {
            $backup->removeVerificationCopy($copy);
        }
    }
}
