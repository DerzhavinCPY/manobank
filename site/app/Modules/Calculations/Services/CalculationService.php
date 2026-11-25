<?php

declare(strict_types=1);

namespace App\Modules\Calculations\Services;

use App\Modules\Calculations\Models\Calculation;
use App\Modules\Catalog\Models\Calculator;
use App\Modules\Deposit\Services\DepositCalculator;
use App\Modules\Loans\Services\LoanCalculator;
use App\Modules\Pension\Services\PensionCalculator;
use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class CalculationService
{
    public function __construct(
        private readonly LoanCalculator $loans,
        private readonly PensionCalculator $pension,
        private readonly DepositCalculator $deposit,
    ) {}

    public function create(Calculator $product, array $input, string $sessionId): Calculation
    {
        $now = CarbonImmutable::now('+04:00');
        $input['amount'] = (string) BigDecimal::of((string) $input['amount'])->strippedOfTrailingZeros();
        if (isset($input['years'])) {
            $input['years'] = (int) $input['years'];
        }
        if (isset($input['months'])) {
            $input['months'] = (int) $input['months'];
        }
        $result = match ($product->type) {
            'pension' => $this->pension->calculate($input['amount'], $input['years']),
            'deposit' => $this->deposit->calculate($input['amount'], $input['months'], $now->toDateTimeImmutable()),
            default => $this->loans->calculate($input['amount'], $product->rate, 12 * $input['years'], (string) ($input['down_payment'] ?? '0')),
        };

        $comparison = null;
        if (isset($input['salary']) && $input['salary'] !== '' && isset($result['summary']['recommended_income'])) {
            $comparison = ['sufficient' => BigRational::of((string) $input['salary'])->isGreaterThanOrEqualTo($result['summary']['recommended_income'])];
        }

        return Calculation::query()->create([
            'public_id' => (string) Str::uuid(),
            'calculator_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'session_hash' => hash('sha256', $sessionId),
            'payload' => [
                'version' => 2,
                'product' => ['code' => $product->code, 'name' => $product->name, 'type' => $product->type, 'rate' => $product->rate],
                'inputs' => $input,
                'calculated_at' => $now->toIso8601String(),
                'result' => $result,
                'income_comparison' => $comparison,
            ],
        ]);
    }

    public function owned(string $publicId, string $sessionId): Calculation
    {
        $record = Calculation::query()->where('public_id', $publicId)->firstOrFail();
        abort_unless(hash_equals($record->session_hash, hash('sha256', $sessionId)), 404);

        return $record;
    }
}
