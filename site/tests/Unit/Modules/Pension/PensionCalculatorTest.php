<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pension;

use App\Modules\Pension\Services\PensionCalculator;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\TestCase;

final class PensionCalculatorTest extends TestCase
{
    public function test_every_balance_is_carried_to_the_next_month_without_precision_loss(): void
    {
        $result = (new PensionCalculator)->calculate('3000', 22);
        self::assertTrue(BigDecimal::of($result['summary']['balance_at_retirement'])->isEqualTo($result['rows'][0]['opening_balance']));
        for ($month = 1; $month < 240; $month++) {
            self::assertTrue(BigDecimal::of($result['rows'][$month - 1]['closing_balance'])->isEqualTo($result['rows'][$month]['opening_balance']));
        }
    }

    public function test_twenty_two_years_matches_the_approved_screen(): void
    {
        $result = (new PensionCalculator)->calculate('3000', 22);
        self::assertSame('1476541', $this->rubles($result['summary']['balance_at_retirement']));
        self::assertSame('720000', $result['summary']['contribution_total']);
        $this->assertPayments($result, ['6183', '6532', '8298', '16682', '20365']);
    }

    public function test_short_participation_keeps_support_for_each_year_and_all_240_payments(): void
    {
        foreach ([[3, '108000', '216000', ['905', '956', '1214', '2440', '2979']], [5, '180000', '360000', ['1508', '1593', '2023', '4067', '4965']]] as [$years, $support, $balance, $payments]) {
            $result = (new PensionCalculator)->calculate('3000', $years);
            self::assertSame($support, $result['summary']['state_support']);
            self::assertSame($balance, $this->rubles($result['summary']['balance_at_retirement']));
            $this->assertPayments($result, $payments);
        }
    }

    public function test_higher_contribution_does_not_increase_the_state_support(): void
    {
        $result = (new PensionCalculator)->calculate('20000', 10);
        self::assertSame('360000', $result['summary']['state_support']);
        self::assertSame('2760000', $result['summary']['contribution_total']);
    }

    public function test_base_payments_and_one_extra_year_match_the_other_reference_controls(): void
    {
        $engine = new PensionCalculator;
        $this->assertPayments($engine->calculate('3000', 10), ['3015', '3185', '4047', '8135', '9931']);
        self::assertSame('764408', $this->rubles($engine->calculate('3000', 11)['summary']['balance_at_retirement']));
    }

    public function test_zero_years_is_allowed_by_the_agreed_slider_range(): void
    {
        $result = (new PensionCalculator)->calculate('3000', 0);
        self::assertCount(240, $result['rows']);
        self::assertSame('0', $this->rubles($result['summary']['balance_at_retirement']));
        self::assertSame('0', $this->rubles($result['rows'][0]['payment']));
    }

    private function assertPayments(array $result, array $expected): void
    {
        self::assertCount(240, $result['rows']);
        foreach ([1, 12, 60, 200, 240] as $i => $month) {
            self::assertSame($expected[$i], $this->rubles($result['rows'][$month - 1]['payment']), 'Month '.$month);
        }
        self::assertTrue(BigDecimal::of($result['rows'][239]['closing_balance'])->isZero());
    }

    private function rubles(string $amount): string
    {
        return (string) BigDecimal::of($amount)->toScale(0, RoundingMode::HalfUp);
    }
}
