<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Site;

use App\Modules\Catalog\Database\Seeders\CalculatorSeeder;
use App\Modules\Catalog\Models\Calculator;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CalculatorSeeder::class);
    }

    public function test_start_page_shows_exactly_the_five_public_products(): void
    {
        Calculator::query()->create(['name' => 'Внутренний вклад', 'type' => 'deposit', 'rate' => '7', 'parameters' => []]);
        $response = $this->get('/')->assertOk()->assertSee('Выберите наш продукт, чтобы перейти к расчету');
        $xpath = $this->html($response->getContent());
        self::assertSame(5, $xpath->query('//*[@data-product-card]')->length);
        self::assertSame(0, $xpath->query('//header//a[normalize-space()="О банке"]')->length);
        $response->assertDontSee('Внутренний вклад');
    }

    public function test_every_calculator_opens_with_empty_fields_and_zero_results(): void
    {
        foreach (['mortgage', 'auto', 'consumer', 'pension', 'deposit'] as $code) {
            $response = $this->get('/calculator/'.$code)->assertOk()->assertSee('Рассчитать')->assertSee('Сбросить')->assertSee('Отправить полный расчёт на Email');
            $xpath = $this->html($response->getContent());
            foreach ($xpath->query('//form[@data-calculator]//input[@name and @type!="hidden" and @type!="radio"]') as $input) {
                self::assertSame('', $input->getAttribute('value'), $code.' '.$input->getAttribute('name'));
            }
            self::assertSame(0, $xpath->query('//form[@data-calculator]//input[@checked]')->length);
            self::assertGreaterThanOrEqual(4, $xpath->query('//*[@data-metric-value]')->length);
            foreach ($xpath->query('//*[@data-metric-value]') as $metric) {
                $label = trim($metric->parentNode->firstChild->textContent);
                $expected = $label === 'Срок кредита' ? '0 лет' : ($label === 'Срок вклада' ? '0 мес.' : '0 ₽');
                self::assertSame($expected, trim($metric->textContent));
            }
        }
    }

    public function test_navigation_placeholders_and_footer_destinations_are_real_pages(): void
    {
        foreach (['/home', '/products', '/support', '/privacy', '/terms', '/pension-program'] as $path) {
            $this->get($path)->assertOk()->assertSee('Технические работы');
        }
        $this->get('/calculator')->assertOk()->assertSee('Какой расчёт вам нужен?');
        $this->get('/account')->assertOk()->assertSee('Номер телефона')->assertSee('Введите номер карты');
    }

    public function test_pension_page_shows_accumulated_total_alongside_the_five_payment_results(): void
    {
        $response = $this->get('/calculator/pension')->assertOk()->assertSee('Сумма к началу выплат');
        $xpath = $this->html($response->getContent());
        self::assertSame('0 ₽', trim($xpath->query('//*[@data-retirement-balance]')->item(0)->textContent));
        self::assertSame(5, $xpath->query('//*[@data-metric-value]')->length);
        $this->get('/calculator/mortgage')->assertOk()->assertDontSee('Сумма к началу выплат');
    }

    public function test_footer_offers_protected_administrator_access_below_the_support_email(): void
    {
        $response = $this->get('/calculator')->assertOk()->assertSee('Вход для администратора');
        $xpath = $this->html($response->getContent());
        $links = $xpath->query('//footer//section[h2="Поддержка"]/a');
        self::assertSame('supp.manobank@mail.ru', trim($links->item(0)->textContent));
        self::assertSame('Вход для администратора', trim($links->item(1)->textContent));
        $this->get($links->item(1)->getAttribute('href'))->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Пароль');
    }

    public function test_contacts_use_the_approved_entrance_and_map(): void
    {
        $this->get('/contacts')->assertOk()->assertSee('426009')->assertSee('Ленина, д. 112')
            ->assertSee('mano.2004@mail.ru')->assertSee('supp.manobank@mail.ru')->assertSee('Как добраться');
        $this->get('/media/entrance')->assertOk()->assertHeader('content-type', 'image/png');
        $this->get('/media/map')->assertOk()->assertHeader('content-type', 'image/jpeg');
        $this->get('/media/unknown')->assertNotFound();
    }

    public function test_requisites_keep_the_leading_zero_of_the_bank_code(): void
    {
        $this->get('/requisites')->assertOk()->assertSee('Реквизиты ПАО «МаноБанк»')
            ->assertSee('1831999009')->assertSee('183101001')->assertSee('1251800999009')
            ->assertSee('40702810100000000909')->assertSee('30101810300000000999')->assertSee('049499999');
    }

    public function test_unknown_calculator_does_not_render_another_product(): void
    {
        $this->get('/calculator/internal')->assertNotFound();
    }

    private function html(string $content): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$content);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
