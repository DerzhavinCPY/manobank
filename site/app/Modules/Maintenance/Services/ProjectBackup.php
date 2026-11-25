<?php

declare(strict_types=1);

namespace App\Modules\Maintenance\Services;

use Illuminate\Support\Facades\File;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

final class ProjectBackup
{
    private function root(): string
    {
        return realpath(base_path('..'));
    }

    public function create(?string $databaseFile = null): string
    {
        $directory = $this->root().'/temp/runtime/backups';
        File::ensureDirectoryExists($directory);
        $database = realpath($databaseFile ?? config('database.connections.sqlite.database'));
        if ($database === false || ! is_file($database) || ! $this->inside($database, $this->root())) {
            throw new RuntimeException('Для резервного копирования нужна локальная SQLite внутри проекта.');
        }
        $suffix = bin2hex(random_bytes(8));
        $copy = $directory.'/snapshot-'.$suffix.'.sqlite';
        $archive = $directory.'/manobank-'.now()->format('Ymd-His').'-'.$suffix.'.zip';
        $zip = new ZipArchive;
        try {
            $pdo = new PDO('sqlite:'.$database, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('PRAGMA busy_timeout=5000');
            $pdo->exec('VACUUM INTO '.$pdo->quote($copy));
            $pdo = null;
            if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('Не удалось создать архив.');
            }
            $manifest = ['format' => 1, 'created_at' => now()->toIso8601String(), 'files' => []];
            foreach ($this->sourceFiles() as $relative => $absolute) {
                $bytes = file_get_contents($absolute);
                $zip->addFromString($relative, $bytes);
                $manifest['files'][$relative] = hash('sha256', $bytes);
            }
            $bytes = file_get_contents($copy);
            $zip->addFromString('site/database/database.sqlite', $bytes);
            $manifest['files']['site/database/database.sqlite'] = hash('sha256', $bytes);
            $zip->addFromString('backup-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            if (! $zip->close()) {
                throw new RuntimeException('Не удалось завершить архив.');
            }

            return $archive;
        } finally {
            if (is_file($copy)) {
                File::delete($copy);
            }
        }
    }

    public function extractVerified(string $archive): string
    {
        $resolved = realpath($archive);
        if ($resolved === false || ! $this->inside($resolved, $this->root().'/temp/runtime/backups')) {
            throw new RuntimeException('Архив должен находиться в локальной папке резервных копий проекта.');
        }
        $zip = new ZipArchive;
        if ($zip->open($resolved) !== true) {
            throw new RuntimeException('Не удалось открыть архив.');
        }
        $target = $this->root().'/temp/runtime/restore-'.bin2hex(random_bytes(8));
        try {
            $manifestText = $zip->getFromName('backup-manifest.json');
            if ($manifestText === false) {
                throw new RuntimeException('В архиве отсутствует манифест.');
            }
            $manifest = json_decode($manifestText, true, 32, JSON_THROW_ON_ERROR);
            if (($manifest['format'] ?? null) !== 1 || ! is_array($manifest['files'] ?? null) || $zip->numFiles !== count($manifest['files']) + 1) {
                throw new RuntimeException('Некорректный состав архива.');
            }
            $size = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                $stat = $zip->statIndex($index);
                $size += $stat['size'];
                $zip->getExternalAttributesIndex($index, $system, $attributes);
                if (str_contains($name, '\\') || str_contains($name, ':') || str_starts_with($name, '/')
                    || preg_match('~(^|/)\.\.?(/|$)~', $name) || (($attributes >> 16) & 0170000) === 0120000
                    || $size > 536870912 || ($name !== 'backup-manifest.json' && ! isset($manifest['files'][$name]))) {
                    throw new RuntimeException('Небезопасный путь или размер архива.');
                }
            }
            foreach ($manifest['files'] as $name => $hash) {
                $bytes = $zip->getFromName($name);
                if ($bytes === false || ! hash_equals((string) $hash, hash('sha256', $bytes))) {
                    throw new RuntimeException('Контрольная сумма файла не совпала.');
                }
                $destination = $target.'/'.$name;
                File::ensureDirectoryExists(dirname($destination));
                file_put_contents($destination, $bytes);
            }

            return $target;
        } catch (\Throwable $exception) {
            if (is_dir($target)) {
                $this->removeVerificationCopy($target);
            }
            throw $exception;
        } finally {
            $zip->close();
        }
    }

    public function removeVerificationCopy(string $path): void
    {
        $resolved = realpath($path);
        if ($resolved === false || ! $this->inside($resolved, $this->root().'/temp/runtime') || ! preg_match('/^restore-[a-f0-9]{16}$/', basename($resolved))) {
            throw new RuntimeException('Отказ удаления: путь не является проверочной копией проекта.');
        }
        File::deleteDirectory($resolved);
    }

    public function sourceFiles(): array
    {
        $files = [];
        foreach (['app', 'config', 'routes', 'resources', 'database', 'public'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory), RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->isLink() || ! $this->inside($file->getRealPath(), base_path($directory))) {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (in_array($file->getBasename(), ['AGENTS.md', '.env', 'hot'], true) || str_contains($relative, '.sqlite')) {
                    continue;
                }
                $files['site/'.$relative] = $file->getPathname();
            }
        }
        foreach (['artisan', '.env.example', 'README.md', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.js', 'bootstrap/app.php', 'bootstrap/providers.php'] as $file) {
            $files['site/'.$file] = base_path($file);
        }
        foreach (['entrance/ВГ-5.png', 'location/Ижевск_Ленина_112.jpg'] as $asset) {
            $files['visualizations/manobank/'.$asset] = $this->root().'/visualizations/manobank/'.$asset;
        }
        ksort($files);

        return $files;
    }

    private function inside(string $path, string $directory): bool
    {
        return str_starts_with(str_replace('\\', '/', $path), rtrim(str_replace('\\', '/', $directory), '/').'/');
    }
}
