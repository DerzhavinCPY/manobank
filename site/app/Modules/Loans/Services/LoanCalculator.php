<?php

declare(strict_types=1);

namespace App\Modules\Loans\Services;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class LoanCalculator
{
    public function calculate(string $amount, string $annualRate, int $months, string $downPaymentPercent = '0'): array
    {
        $price = BigDecimal::of($amount);
        $rate = BigDecimal::of($annualRate);
        $downPercent = BigDecimal::of($downPaymentPercent);

        if (! $price->isPositive() || ! $rate->isPositive() || $months < 1 || $downPercent->isLessThan(0) || $downPercent->isGreaterThanOrEqualTo(100)) {
            throw new InvalidArgumentException('Сумма кредита, ставка и срок должны быть положительными.');
        }

        $down = $price->multipliedBy($downPercent)->dividedByExact(100);
        $principal = $price->minus($down)->strippedOfTrailingZeros();
        $monthlyRate = $rate->toBigRational()->dividedBy(1200);
        $factor = $monthlyRate->plus(1)->power($months);
        $payment = $principal->toBigRational()->multipliedBy($monthlyRate)->multipliedBy($factor)->dividedBy($factor->minus(1));
        $total = $payment->multipliedBy($months);
        $balance = $principal->toBigRational();
        $rows = [];

        for ($month = 1; $month <= $months; $month++) {
            $interest = $balance->multipliedBy($monthlyRate);
            $principalPayment = $payment->minus($interest);
            $closing = $balance->minus($principalPayment);
            $rows[] = [
                'month' => $month,
                'opening_balance' => (string) $balance,
                'payment' => (string) $payment,
                'interest' => (string) $interest,
                'principal_payment' => (string) $principalPayment,
                'closing_balance' => (string) $closing,
            ];
            $balance = $closing;
        }

        return [
            'summary' => [
                'amount' => (string) $price,
                'down_payment' => (string) $down->strippedOfTrailingZeros(),
                'principal' => (string) $principal,
                'months' => $months,
                'rate' => (string) $rate,
                'monthly_payment' => (string) $payment,
                'recommended_income' => (string) $payment->multipliedBy('2.5'),
                'total_payment' => (string) $total,
                'overpayment' => (string) $total->minus($principal),
            ],
            'rows' => $rows,
        ];
    }
}
