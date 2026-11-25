<?php

namespace Tests\Feature\Modules\Maintenance;

use App\Modules\Maintenance\Services\ReleasePackage;
use Tests\TestCase;
use ZipArchive;

final class ReleasePackageTest extends TestCase
{
    public function test_hosting_package_has_configuration_example_and_no_private_database_or_local_rules(): void
    {
        $bytes = app(ReleasePackage::class)->bytes();
        $path = tempnam(sys_get_temp_dir(), 'release-check-');
        try {
            file_put_contents($path, $bytes);
            $zip = new ZipArchive;
            self::assertTrue($zip->open($path));
            foreach (['site/.env.example', 'site/bootstrap/app.php', 'site/composer.lock', 'visualizations/manobank/entrance/ВГ-5.png', 'release-manifest.json'] as $file) {
                self::assertNotFalse($zip->locateName($file));
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                self::assertNotSame('.env', basename($name));
                self::assertNotSame('AGENTS.md', basename($name));
                self::assertStringNotContainsString('.sqlite', $name);
                self::assertStringNotContainsString('.git/', $name);
            }
            $zip->close();
        } finally {
            @unlink($path);
        }
    }
}
