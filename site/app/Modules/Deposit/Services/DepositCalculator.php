<?php

declare(strict_types=1);

namespace App\Modules\Deposit\Services;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class DepositCalculator
{
    public function calculate(string $amount, int $months, DateTimeImmutable $openedAt): array
    {
        $principal = BigDecimal::of($amount);
        if ($principal->isLessThan(50000) || ! in_array($months, [3, 6, 9, 12], true)) {
            throw new InvalidArgumentException('Проверьте сумму и срок вклада.');
        }

        $opened = $openedAt->setTimezone(new DateTimeZone('+04:00'))->setTime(0, 0);
        $firstTargetDay = $opened->modify('first day of this month')->modify('+'.$months.' months');
        $day = min((int) $opened->format('j'), (int) $firstTargetDay->format('t'));
        $maturity = $firstTargetDay->setDate((int) $firstTargetDay->format('Y'), (int) $firstTargetDay->format('n'), $day);
        $interest = BigRational::zero();
        $rows = [];

        for ($date = $opened->modify('+1 day'); $date <= $maturity; $date = $date->modify('+1 day')) {
            $yearDays = $date->format('L') === '1' ? 366 : 365;
            $daily = $principal->toBigRational()->multipliedBy('0.07')->dividedBy($yearDays);
            $interest = $interest->plus($daily);
            $rows[] = [
                'date' => $date->format('Y-m-d'),
                'opening_balance' => (string) $principal,
                'interest' => (string) $daily,
                'accrued_interest' => (string) $interest,
                'closing_balance' => (string) $principal,
            ];
        }

        return [
            'summary' => [
                'amount' => (string) $principal,
                'months' => $months,
                'rate' => '7',
                'opened_at' => $opened->format('Y-m-d'),
                'matures_at' => $maturity->format('Y-m-d'),
                'days' => count($rows),
                'interest' => (string) $interest,
                'total' => (string) $interest->plus($principal),
            ],
            'rows' => $rows,
        ];
    }
}
