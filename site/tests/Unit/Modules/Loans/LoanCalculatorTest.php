<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Loans;

use App\Modules\Calculations\Services\MoneyFormatter;
use App\Modules\Loans\Services\LoanCalculator;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\TestCase;

final class LoanCalculatorTest extends TestCase
{
    public function test_exact_half_ruble_interest_is_not_lost_by_the_monthly_rate(): void
    {
        $result = (new LoanCalculator)->calculate('102000', '14.5', 12);
        self::assertSame('1 233 ₽', (new MoneyFormatter)->rubles($result['rows'][0]['interest']));
    }

    public function test_mortgage_matches_the_assignment_and_keeps_the_unrounded_payment(): void
    {
        $result = (new LoanCalculator)->calculate('2000000', '9.6', 240, '25');

        foreach (['principal' => '1500000', 'down_payment' => '500000', 'monthly_payment' => '14080', 'recommended_income' => '35200', 'total_payment' => '3379216', 'overpayment' => '1879216'] as $key => $expected) {
            self::assertSame($expected, (string) BigRational::of($result['summary'][$key])->toScale(0, RoundingMode::HalfUp), $key);
        }
        self::assertCount(240, $result['rows']);
        self::assertSame('12000', (string) BigRational::of($result['rows'][0]['interest'])->toScale(0));
        self::assertSame($result['summary']['monthly_payment'], $result['rows'][239]['payment']);
        self::assertTrue(BigRational::of($result['rows'][239]['closing_balance'])->isZero());
        self::assertNotSame('14080', $result['summary']['monthly_payment']);
    }

    public function test_auto_and_consumer_examples_use_the_same_annuity_engine(): void
    {
        $engine = new LoanCalculator;
        foreach ([['1500000', '3.5', 60, '20', '21830', '54575'], ['300000', '14.5', 36, '0', '10326', '25816']] as [$amount, $rate, $months, $down, $payment, $income]) {
            $result = $engine->calculate($amount, $rate, $months, $down);
            self::assertSame($payment, (string) BigRational::of($result['summary']['monthly_payment'])->toScale(0, RoundingMode::HalfUp));
            self::assertSame($income, (string) BigRational::of($result['summary']['recommended_income'])->toScale(0, RoundingMode::HalfUp));
            self::assertCount($months, $result['rows']);
        }
    }

    public function test_zero_initial_payment_finances_the_entire_purchase(): void
    {
        $result = (new LoanCalculator)->calculate('100000', '3.5', 12, '0');
        self::assertSame('100000', $result['summary']['principal']);
    }

    public function test_full_initial_payment_is_not_a_positive_loan(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new LoanCalculator)->calculate('100000', '9.6', 12, '100');
    }
}
