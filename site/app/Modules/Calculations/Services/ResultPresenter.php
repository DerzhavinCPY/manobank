<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Services;

use DateTimeImmutable;

final class ResultPresenter
{
    public function __construct(private readonly MoneyFormatter $money) {}

    public function present(array $payload): array
    {
        $type = $payload['product']['type'];
        $summary = [];
        foreach ($payload['result']['summary'] as $key => $value) {
            $summary[$key] = match ($key) {
                'months', 'days', 'years_until_retirement', 'contribution_years', 'capitalization_months' => $value,
                'rate' => str_replace('.', ',', $value),
                'opened_at', 'matures_at' => (new DateTimeImmutable($value))->format('d.m.Y'),
                default => $this->money->rubles($value),
            };
        }
        $headers = $type === 'deposit'
            ? ['date' => 'Дата', 'opening_balance' => 'Сумма вклада', 'interest' => 'Проценты за день', 'accrued_interest' => 'Начислено всего']
            : ['month' => 'Месяц', 'opening_balance' => 'Остаток на начало', 'payment' => 'Выплата', 'interest' => 'Проценты', 'closing_balance' => 'Остаток'];
        if (in_array($type, ['mortgage', 'auto', 'consumer'], true)) {
            $headers['payment'] = 'Платёж';
            $headers = ['month' => 'Месяц', 'opening_balance' => 'Долг на начало', 'payment' => 'Платёж', 'principal_payment' => 'Основной долг', 'interest' => 'Проценты', 'closing_balance' => 'Остаток долга'];
        }
        $rows = [];
        foreach ($payload['result']['rows'] as $raw) {
            $row = [];
            foreach ($headers as $key => $label) {
                $row[$key] = $key === 'month' ? $raw[$key] : ($key === 'date' ? (new DateTimeImmutable($raw[$key]))->format('d.m.Y') : $this->money->rubles($raw[$key]));
            }
            $rows[] = $row;
        }
        $metrics = [];
        if ($type === 'pension') {
            foreach ([1, 12, 60, 200, 240] as $month) {
                $metrics[] = ['label' => $month.'-й месяц', 'value' => $rows[$month - 1]['payment']];
            }
        } elseif ($type === 'deposit') {
            foreach (['amount' => 'Сумма вклада', 'months' => 'Срок вклада', 'interest' => 'Процентный доход', 'total' => 'К получению в конце срока'] as $key => $label) {
                $metrics[] = ['label' => $label, 'value' => $key === 'months' ? $summary[$key].' мес.' : $summary[$key]];
            }
        } else {
            $metrics = [
                ['label' => 'Срок кредита', 'value' => $payload['inputs']['years'].' '.$this->yearsWord($payload['inputs']['years'])],
                ['label' => 'Сумма кредита', 'value' => $summary['principal']],
                ['label' => 'Ежемесячный платёж', 'value' => $summary['monthly_payment']],
                ['label' => 'Рекомендуемая зарплата', 'value' => $summary['recommended_income']],
            ];
        }

        return [
            'product' => $payload['product'],
            'inputs' => $this->inputs($payload),
            'calculated_at' => (new DateTimeImmutable($payload['calculated_at']))->format('d.m.Y H:i'),
            'summary' => $summary,
            'metrics' => $metrics,
            'row_headers' => $headers,
            'rows' => $rows,
            'income_comparison' => $payload['income_comparison'],
        ];
    }

    private function inputs(array $payload): array
    {
        $input = $payload['inputs'];
        $type = $payload['product']['type'];
        $result = ['Сумма' => $this->money->rubles($input['amount']), 'Ставка' => str_replace('.', ',', $payload['product']['rate']).'% годовых'];
        if (isset($input['years'])) {
            $result[$type === 'pension' ? 'До начала выплат' : 'Срок кредита'] = $input['years'].' '.$this->yearsWord($input['years']);
        }
        if (isset($input['months'])) {
            $result['Срок вклада'] = $input['months'].' мес.';
        }
        if (isset($input['down_payment'])) {
            $result['Первоначальный взнос'] = str_replace('.', ',', (string) $input['down_payment']).'%';
        }
        if (isset($input['salary']) && $input['salary'] !== '') {
            $result['Заработная плата'] = $this->money->rubles((string) $input['salary']);
        }
        if (isset($input['gender'])) {
            $result['Возраст начала выплат'] = $input['gender'] === 'male' ? 'Мужчина — 65 лет' : 'Женщина — 60 лет';
        }

        return $result;
    }

    private function yearsWord(int $years): string
    {
        return $years % 100 >= 11 && $years % 100 <= 14 ? 'лет' : match ($years % 10) {
            1 => 'год', 2, 3, 4 => 'года', default => 'лет'
        };
    }
}
