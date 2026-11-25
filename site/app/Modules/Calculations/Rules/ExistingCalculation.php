<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Rules;

use App\Modules\Calculations\Models\Calculation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ExistingCalculation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Calculation::query()->where('public_id', $value)->exists()) {
            $fail('Один из выбранных расчётов не найден. Обновите страницу и выберите расчёты снова.');
        }
    }
}
