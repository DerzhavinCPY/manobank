<?php

declare(strict_types=1);

namespace App\Modules\Pension\Services;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class PensionCalculator
{
    public function calculate(string $monthlyContribution, int $yearsUntilRetirement): array
    {
        $contribution = BigDecimal::of($monthlyContribution);
        if ($contribution->isLessThan(3000) || $contribution->isGreaterThan(20000) || $yearsUntilRetirement < 0 || $yearsUntilRetirement > 50) {
            throw new InvalidArgumentException('Проверьте сумму взноса и количество лет до пенсии.');
        }

        $years = min($yearsUntilRetirement, 10);
        $own = $contribution->multipliedBy(12 * $years);
        $support = BigDecimal::of(36000 * $years);
        $total = $own->plus($support);
        $growthMonths = 12 * max($yearsUntilRetirement - 10, 0);
        $atRetirement = $total->multipliedBy(BigDecimal::of('1.005')->power($growthMonths));
        $balance = $atRetirement;
        $rows = [];

        for ($month = 1; $month <= 240; $month++) {
            $remaining = 241 - $month;
            $withInterest = $balance->multipliedBy('1.005');
            $payment = $withInterest->dividedByExact($remaining);
            $closing = $withInterest->minus($payment);
            $rows[] = [
                'month' => $month,
                'opening_balance' => (string) $balance,
                'interest' => (string) $withInterest->minus($balance),
                'payment' => (string) $payment,
                'closing_balance' => (string) $closing,
            ];
            $balance = $closing;
        }

        return [
            'summary' => [
                'monthly_contribution' => (string) $contribution,
                'years_until_retirement' => $yearsUntilRetirement,
                'contribution_years' => $years,
                'own_contributions' => (string) $own->strippedOfTrailingZeros(),
                'state_support' => (string) $support,
                'contribution_total' => (string) $total->strippedOfTrailingZeros(),
                'capitalization_months' => $growthMonths,
                'balance_at_retirement' => (string) $atRetirement,
                'months' => 240,
                'rate' => '6',
            ],
            'rows' => $rows,
        ];
    }
}
