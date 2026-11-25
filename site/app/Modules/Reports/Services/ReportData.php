<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Modules\Calculations\Services\ResultPresenter;

final class ReportData
{
    private const LABELS = [
        'amount' => 'Стоимость / сумма', 'down_payment' => 'Первоначальный взнос', 'principal' => 'Сумма кредита',
        'months' => 'Срок, месяцев', 'rate' => 'Годовая ставка, %', 'monthly_payment' => 'Ежемесячный платёж',
        'recommended_income' => 'Рекомендуемая зарплата', 'total_payment' => 'Общая сумма выплат', 'overpayment' => 'Переплата',
        'monthly_contribution' => 'Ежемесячный взнос', 'years_until_retirement' => 'До пенсии, лет',
        'contribution_years' => 'Период взносов, лет', 'own_contributions' => 'Личные взносы', 'state_support' => 'Господдержка',
        'contribution_total' => 'Сумма после этапа взносов', 'capitalization_months' => 'Капитализация до выплат, месяцев',
        'balance_at_retirement' => 'Сумма к началу выплат', 'opened_at' => 'Дата открытия', 'matures_at' => 'Дата окончания',
        'days' => 'Дней начисления', 'interest' => 'Процентный доход', 'total' => 'К получению в конце срока',
    ];

    public function __construct(private readonly ResultPresenter $presenter) {}

    public function prepare(array $snapshot): array
    {
        $result = $this->presenter->present($snapshot);
        $summary = [];
        foreach ($result['summary'] as $key => $value) {
            $summary[self::LABELS[$key] ?? $key] = $value;
        }
        $result['summary_labels'] = $summary;
        $result['conditions'] = match ($snapshot['product']['type']) {
            'pension' => 'Взносы без пропусков. Господдержка — 36 000 ₽ за год участия, не более 10 лет. На этапе взносов проценты не начисляются. После него — 6% годовых с ежемесячной капитализацией; выплаты в течение 240 месяцев с начислением процентов на остаток.',
            'deposit' => '7% годовых без капитализации. Начисление ежедневно со следующего дня по дату окончания включительно, по календарю и длительности соответствующего года. Расчёт без пополнений и снятий. При досрочном закрытии процентный доход теряется.',
            default => 'Аннуитетный расчёт без дополнительных комиссий и досрочного погашения.',
        };

        return $result;
    }
}
