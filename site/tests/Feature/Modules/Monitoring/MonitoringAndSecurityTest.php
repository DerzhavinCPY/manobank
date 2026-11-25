<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Monitoring;

use App\Modules\Catalog\Database\Seeders\CalculatorSeeder;
use App\Modules\Monitoring\Models\TechnicalEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MonitoringAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CalculatorSeeder::class);
    }

    public function test_headers_and_indexing_do_not_expose_administration(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/admin/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/support')->assertOk()->assertSee('noindex, follow');
    }

    public function test_sitemap_uses_the_configured_origin_and_robots_exclude_private_paths(): void
    {
        config(['app.url' => 'https://bank.example']);
        $this->get('/sitemap.xml')->assertOk()->assertSee('https://bank.example/calculator/mortgage')->assertDontSee('/admin');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Disallow: /calculations')->assertSee('Sitemap: https://bank.example/sitemap.xml');
        $this->withHeader('Host', 'untrusted.example')->get('/calculator/mortgage')->assertOk()->assertSee('https://bank.example/calculator/mortgage')->assertDontSee('rel="canonical" href="http://untrusted.example', false);
    }

    public function test_statistics_are_real_scoped_and_contain_no_form_bodies(): void
    {
        $this->postJson('/calculations/consumer', ['amount' => 100000, 'years' => 1, 'password' => 'not-recorded', 'card' => 'not-recorded'])->assertCreated();
        $event = TechnicalEvent::query()->where('route_name', 'calculations.store')->firstOrFail();
        self::assertSame(201, $event->status);
        self::assertTrue($event->verification);
        self::assertGreaterThanOrEqual(0, $event->duration_ms);
        self::assertStringNotContainsString('not-recorded', $event->toJson());
        $this->artisan('manobank:statistics', ['--verification' => true])->assertSuccessful();
    }

    public function test_calculation_rate_limit_prevents_unbounded_requests(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/calculations/consumer', [])->assertUnprocessable();
        }
        $this->postJson('/calculations/consumer', [])->assertStatus(429);
    }
}
