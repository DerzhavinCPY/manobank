<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Deposit;

use App\Modules\Calculations\Services\MoneyFormatter;
use App\Modules\Deposit\Services\DepositCalculator;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DepositCalculatorTest extends TestCase
{
    public function test_exact_half_ruble_annual_income_is_rounded_up_after_summing(): void
    {
        $result = (new DepositCalculator)->calculate('50250', 12, new DateTimeImmutable('2026-11-25T00:00:00+04:00'));
        self::assertSame(365, $result['summary']['days']);
        self::assertSame('3 518 ₽', (new MoneyFormatter)->rubles($result['summary']['interest']));
        self::assertSame('53 768 ₽', (new MoneyFormatter)->rubles($result['summary']['total']));
    }

    public function test_ninety_days_excludes_opening_day_and_includes_maturity(): void
    {
        $result = (new DepositCalculator)->calculate('50000', 3, new DateTimeImmutable('2025-01-01T12:00:00+04:00'));
        self::assertSame('2025-01-01', $result['summary']['opened_at']);
        self::assertSame('2025-04-01', $result['summary']['matures_at']);
        self::assertCount(90, $result['rows']);
        self::assertSame('2025-01-02', $result['rows'][0]['date']);
        self::assertSame('2025-04-01', $result['rows'][89]['date']);
        self::assertSame('863', (string) BigRational::of($result['summary']['interest'])->toScale(0, RoundingMode::HalfUp));
    }

    public function test_a_leap_year_uses_366_days(): void
    {
        $result = (new DepositCalculator)->calculate('50000', 3, new DateTimeImmutable('2024-01-01T12:00:00+04:00'));
        self::assertCount(91, $result['rows']);
        self::assertSame('870', (string) BigRational::of($result['summary']['interest'])->toScale(0, RoundingMode::HalfUp));
    }

    public function test_exactly_ninety_days_in_a_leap_year_match_the_reference(): void
    {
        $result = (new DepositCalculator)->calculate('50000', 3, new DateTimeImmutable('2024-01-31T00:00:00+04:00'));
        self::assertCount(90, $result['rows']);
        self::assertSame('861', (string) BigRational::of($result['summary']['interest'])->toScale(0, RoundingMode::HalfUp));
    }

    public function test_missing_day_at_maturity_uses_the_last_day_of_the_month(): void
    {
        $result = (new DepositCalculator)->calculate('50000', 3, new DateTimeImmutable('2025-01-31T09:00:00+04:00'));
        self::assertSame('2025-04-30', $result['summary']['matures_at']);
        self::assertCount(89, $result['rows']);
    }

    public function test_opening_date_uses_bank_time_instead_of_the_source_timezone(): void
    {
        $result = (new DepositCalculator)->calculate('50000', 3, new DateTimeImmutable('2025-01-01T21:30:00Z'));
        self::assertSame('2025-01-02', $result['summary']['opened_at']);
        self::assertSame('2025-04-02', $result['summary']['matures_at']);
    }

    public function test_interest_is_summed_with_the_denominator_of_each_year(): void
    {
        $result = (new DepositCalculator)->calculate('50000', 3, new DateTimeImmutable('2023-12-01T00:00:00+04:00'));
        self::assertCount(91, $result['rows']);
        self::assertSame('871', (string) BigRational::of($result['summary']['interest'])->toScale(0, RoundingMode::HalfUp));
        self::assertSame('50000', $result['rows'][90]['opening_balance']);
    }
}
