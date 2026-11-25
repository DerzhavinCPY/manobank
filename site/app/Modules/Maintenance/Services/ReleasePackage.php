<?php

declare(strict_types=1);

namespace App\Modules\Maintenance\Services;

use RuntimeException;
use ZipArchive;

final class ReleasePackage
{
    public function __construct(private readonly ProjectBackup $files) {}

    public function bytes(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'manobank-package-');
        $zip = new ZipArchive;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Не удалось создать пакет размещения.');
            }
            $manifest = ['created_at' => now()->toIso8601String(), 'files' => []];
            $files = $this->files->sourceFiles();
            $root = realpath(base_path('..'));
            foreach (['tools/project.ps1', 'tools/php/conf.d/project.ini', 'docs/Эксплуатация_МаноБанк.md'] as $file) {
                $files[$file] = $root.'/'.$file;
            }
            foreach ($files as $name => $absolute) {
                $content = file_get_contents($absolute);
                $zip->addFromString($name, $content);
                $manifest['files'][$name] = hash('sha256', $content);
            }
            $zip->addFromString('release-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            if (! $zip->close()) {
                throw new RuntimeException('Не удалось завершить пакет размещения.');
            }

            return file_get_contents($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
