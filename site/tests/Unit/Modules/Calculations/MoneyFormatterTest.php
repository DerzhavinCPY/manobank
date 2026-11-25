<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Calculations;

use App\Modules\Calculations\Services\MoneyFormatter;
use PHPUnit\Framework\TestCase;

final class MoneyFormatterTest extends TestCase
{
    public function test_mathematical_rounding_and_large_integers_do_not_lose_precision(): void
    {
        $formatter = new MoneyFormatter;
        foreach (['904.5' => '905 ₽', '1507.5' => '1 508 ₽', '14080.0652639998' => '14 080 ₽', '0' => '0 ₽', '12345678901234567890.5' => '12 345 678 901 234 567 891 ₽'] as $input => $expected) {
            self::assertSame($expected, $formatter->rubles((string) $input));
        }
    }
}
