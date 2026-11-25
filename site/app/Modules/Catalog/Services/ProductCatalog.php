<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Calculator;
use Illuminate\Database\Eloquent\Collection;

final class ProductCatalog
{
    public const CODES = ['mortgage', 'auto', 'consumer', 'pension', 'deposit'];

    public function publicProducts(): Collection
    {
        return Calculator::query()->where('is_public', true)->whereIn('code', self::CODES)->orderBy('sort_order')->get();
    }

    public function publicProduct(string $code): Calculator
    {
        abort_unless(in_array($code, self::CODES, true), 404);

        return Calculator::query()->where('is_public', true)->where('code', $code)->firstOrFail();
    }

    public static function definitions(): array
    {
        return [
            'mortgage' => ['name' => 'Ипотека', 'type' => 'mortgage', 'rate' => '9.6', 'subtitle' => 'Первоначальный взнос — от 10% стоимости недвижимости. Срок ипотеки до 30 лет.', 'parameters' => ['min_amount' => 100000, 'max_amount' => 15000000, 'min_years' => 1, 'max_years' => 30, 'min_down_payment' => 10]],
            'auto' => ['name' => 'Автокредит', 'type' => 'auto', 'rate' => '3.5', 'subtitle' => 'Срок кредита до 7 лет.', 'parameters' => ['min_amount' => 100000, 'max_amount' => 6000000, 'min_years' => 1, 'max_years' => 7, 'min_down_payment' => 0]],
            'consumer' => ['name' => 'Потребительский кредит', 'type' => 'consumer', 'rate' => '14.5', 'subtitle' => 'Укажите сумму и срок кредита, чтобы оценить ежемесячный платёж.', 'parameters' => ['min_amount' => 100000, 'max_amount' => 6000000, 'min_years' => 1, 'max_years' => 7]],
            'pension' => ['name' => 'Пенсионные накопления', 'type' => 'pension', 'rate' => '6', 'subtitle' => 'Стабильная прибавка к пенсии с растущим ежемесячным платежом', 'parameters' => ['min_amount' => 3000, 'max_amount' => 20000, 'min_years' => 0, 'max_years' => 50]],
            'deposit' => ['name' => 'Вклад повседневный', 'type' => 'deposit', 'rate' => '7', 'subtitle' => 'Проценты начисляются ежедневно. Выплата — в конце срока.', 'parameters' => ['min_amount' => 50000, 'periods' => [3, 6, 9, 12]]],
        ];
    }
}
