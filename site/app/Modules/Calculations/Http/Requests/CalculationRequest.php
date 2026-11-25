<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Http\Requests;

use App\Modules\Catalog\Services\ProductCatalog;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['amount', 'salary', 'down_payment'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => str_replace(["\u{00A0}", "\u{202F}", ' ', ','], ['', '', '', '.'], trim($this->input($key)))]);
            }
        }
    }

    public function rules(): array
    {
        $product = app(ProductCatalog::class)->publicProduct((string) $this->route('product'));
        $parameters = $product->parameters;
        $length = static function (string $attribute, mixed $value, Closure $fail): void {
            if (strlen(str_replace('.', '', (string) $value)) > 64) {
                $fail(':attribute: число слишком длинное.');
            }
        };
        $money = ['bail', 'numeric', 'regex:/^\d+(?:\.\d{1,2})?$/', $length];
        $rules = [
            'amount' => ['required', ...$money, 'min:'.$parameters['min_amount']],
            'rate' => ['prohibited'],
            'annual_rate' => ['prohibited'],
        ];
        if (isset($parameters['max_amount'])) {
            $rules['amount'][] = 'max:'.$parameters['max_amount'];
        }
        if ($product->type === 'deposit') {
            $rules['months'] = ['required', 'integer', Rule::in([3, 6, 9, 12])];
        } else {
            $rules['years'] = ['required', 'integer', 'between:'.$parameters['min_years'].','.$parameters['max_years']];
        }
        if ($product->type === 'pension') {
            $rules['gender'] = ['required', Rule::in(['male', 'female'])];
        } elseif ($product->type !== 'deposit') {
            $rules['salary'] = ['nullable', ...$money, 'min:0'];
            if ($product->type !== 'consumer') {
                $rules['down_payment'] = ['bail', 'required', 'numeric', $length, 'min:'.$parameters['min_down_payment'], 'lt:100', 'regex:/^\d+(?:\.\d+)?$/'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute: заполните поле.',
            'numeric' => ':attribute: введите число.',
            'integer' => ':attribute: укажите целое число.',
            'regex' => ':attribute: проверьте запись числа.',
            'max_digits' => ':attribute: число слишком длинное.',
            'min' => ':attribute: значение меньше допустимого.',
            'max' => ':attribute: значение больше допустимого.',
            'between' => ':attribute: выберите значение в допустимом диапазоне.',
            'lt' => ':attribute: значение должно быть меньше 100%.',
            'in' => ':attribute: выберите одно из предложенных значений.',
            'prohibited' => 'Ставка задана условиями продукта и не изменяется пользователем.',
        ];
    }

    public function attributes(): array
    {
        return ['amount' => 'Сумма', 'years' => 'Количество лет', 'months' => 'Срок вклада', 'gender' => 'Пол', 'salary' => 'Заработная плата', 'down_payment' => 'Первоначальный взнос'];
    }
}
