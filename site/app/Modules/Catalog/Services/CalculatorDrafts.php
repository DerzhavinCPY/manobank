<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Calculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;

final class CalculatorDrafts
{
    public function listing(): LengthAwarePaginator
    {
        return Calculator::query()->orderByDesc('is_public')->orderBy('sort_order')->orderByDesc('id')->paginate(20);
    }

    public function editable(int $id): Calculator
    {
        $record = Calculator::query()->findOrFail($id);
        abort_if($record->is_public || in_array($record->code, ProductCatalog::CODES, true), 403);

        return $record;
    }

    public function save(array $input, ?int $id = null): Calculator
    {
        $record = $id === null ? new Calculator : $this->editable($id);
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:300'],
            'type' => ['required', Rule::in(ProductCatalog::CODES)],
            'rate' => ['required', 'numeric', 'gt:0', 'max:100', 'regex:/^\d+(\.\d{1,4})?$/'],
            'parameters' => ['required', 'string', 'max:5000'],
        ])->validate();
        try {
            $parameters = json_decode($data['parameters'], true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['parameters' => 'Укажите параметры в формате JSON.']);
        }
        if (! is_array($parameters) || array_is_list($parameters)) {
            throw ValidationException::withMessages(['parameters' => 'Параметры должны быть объектом с именованными полями.']);
        }
        $allowed = array_keys(ProductCatalog::definitions()[$data['type']]['parameters']);
        if (array_diff(array_keys($parameters), $allowed) || array_diff($allowed, array_keys($parameters))) {
            throw ValidationException::withMessages(['parameters' => 'Используйте состав параметров выбранного типа калькулятора.']);
        }
        $rules = ['min_amount' => ['required', 'numeric', 'gt:0'], 'max_amount' => ['sometimes', 'numeric', 'gte:min_amount'],
            'min_years' => ['sometimes', 'integer', 'min:0'], 'max_years' => ['sometimes', 'integer', 'gte:min_years', 'max:50'],
            'min_down_payment' => ['sometimes', 'numeric', 'min:0', 'lt:100'],
            'periods' => ['sometimes', 'array', 'min:1'], 'periods.*' => ['integer', Rule::in([3, 6, 9, 12])]];
        $parameterValidation = Validator::make($parameters, $rules);
        if ($parameterValidation->fails()) {
            throw ValidationException::withMessages(['parameters' => 'Проверьте границы сумм, сроков и первоначального взноса.']);
        }
        $data['parameters'] = $parameters;
        $record->fill($data)->save();

        return $record;
    }

    public function delete(int $id): void
    {
        $this->editable($id)->delete();
    }
}
