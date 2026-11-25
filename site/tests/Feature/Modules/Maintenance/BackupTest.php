<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Maintenance;

use App\Modules\Maintenance\Services\ProjectBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_contains_code_consistent_sqlite_and_approved_assets_but_no_secrets(): void
    {
        $directory = base_path('../temp/runtime/backup-test-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($directory);
        $database = $directory.'/fixture.sqlite';
        $pdo = new PDO('sqlite:'.$database);
        $pdo->exec('CREATE TABLE check_data (value TEXT)');
        $pdo->exec("INSERT INTO check_data VALUES ('backup-control')");
        $pdo = null;
        $backup = null;
        $extracted = null;
        try {
            $service = app(ProjectBackup::class);
            $backup = $service->create($database);
            $zip = new ZipArchive;
            self::assertTrue($zip->open($backup));
            self::assertNotFalse($zip->locateName('site/app/Modules/Loans/Services/LoanCalculator.php'));
            self::assertNotFalse($zip->locateName('visualizations/manobank/entrance/ВГ-5.png'));
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                self::assertNotSame('AGENTS.md', basename($name));
                self::assertNotSame('.env', basename($name));
                self::assertStringNotContainsString('/vendor/', $name);
            }
            $zip->close();
            $extracted = $service->extractVerified($backup);
            $restored = new PDO('sqlite:'.$extracted.'/site/database/database.sqlite');
            self::assertSame('ok', $restored->query('PRAGMA integrity_check')->fetchColumn());
            self::assertSame('backup-control', $restored->query('SELECT value FROM check_data')->fetchColumn());
            $restored = null;
        } finally {
            if ($backup !== null) {
                File::delete($backup);
            }
            if ($extracted !== null) {
                app(ProjectBackup::class)->removeVerificationCopy($extracted);
            }
            File::deleteDirectory($directory);
        }
    }

    public function test_restore_rejects_archive_traversal_before_extracting(): void
    {
        $directory = base_path('../temp/runtime/backups');
        File::ensureDirectoryExists($directory);
        $archive = $directory.'/invalid-'.bin2hex(random_bytes(5)).'.zip';
        $zip = new ZipArchive;
        $zip->open($archive, ZipArchive::CREATE);
        $zip->addFromString('../outside.txt', 'must-not-be-extracted');
        $zip->addFromString('backup-manifest.json', json_encode(['format' => 1, 'files' => ['../outside.txt' => hash('sha256', 'must-not-be-extracted')]]));
        $zip->close();
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Небезопасный путь');
            app(ProjectBackup::class)->extractVerified($archive);
        } finally {
            File::delete($archive);
        }
    }
}
