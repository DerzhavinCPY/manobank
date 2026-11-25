<?php

declare(strict_types=1);

use App\Modules\Loans\Services\LoanCalculator;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Http\Request;

$root = dirname(__DIR__, 5);
$site = $root.'/site';
$vendor = realpath($argv[1]);
$loader = require $vendor.'/autoload.php';
$loader->setPsr4('App\\', $site.'/app');
$loader->setPsr4('Database\\Seeders\\', $site.'/database/seeders');
$classMap = [];
foreach ($loader->getClassMap() as $class => $file) {
    if (str_starts_with($class, 'App\\')) {
        $classMap[$class] = $site.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
    }
}
$loader->addClassMap($classMap);
foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/private', 'storage/fonts'] as $directory) {
    if (! is_dir($site.'/'.$directory)) {
        mkdir($site.'/'.$directory, 0777, true);
    }
}
$app = require $site.'/bootstrap/app.php';
$app->make(PackageManifest::class)->vendorPath = $vendor;
$kernel = $app->make(Kernel::class);
$response = $kernel->handle(Request::create('http://localhost/', 'GET'));
$reflection = new ReflectionClass(LoanCalculator::class);
$sourceVerified = str_starts_with(str_replace('\\', '/', $reflection->getFileName()), str_replace('\\', '/', $site).'/');
$integrity = $app['db']->connection()->selectOne('PRAGMA integrity_check');
$request = Request::create('http://localhost/calculations/mortgage', 'POST', ['amount' => 2000000, 'down_payment' => 25, 'years' => 20]);
$request->headers->set('Accept', 'application/json');
$calculation = $kernel->handle($request);
$result = json_decode($calculation->getContent(), true);
$ok = $response->getStatusCode() === 200 && $sourceVerified && reset($integrity) === 'ok' && $calculation->getStatusCode() === 201
    && ($result['summary']['monthly_payment'] ?? '') === '14 080 ₽' && ($result['summary']['recommended_income'] ?? '') === '35 200 ₽';
echo json_encode(['success' => $ok, 'restored_sources' => $sourceVerified, 'sqlite_integrity' => reset($integrity), 'page_status' => $response->getStatusCode(), 'calculation_status' => $calculation->getStatusCode(), 'monthly_payment' => $result['summary']['monthly_payment'] ?? null, 'recommended_income' => $result['summary']['recommended_income'] ?? null], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit($ok ? 0 : 1);
