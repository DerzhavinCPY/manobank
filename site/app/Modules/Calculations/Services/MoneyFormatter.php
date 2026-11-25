<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Services;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

final class MoneyFormatter
{
    public function rubles(string|int $value): string
    {
        $whole = (string) BigRational::of($value)->toScale(0, RoundingMode::HalfUp);

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', $whole).' ₽';
    }
}
