<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Calculations;

use App\Modules\Calculations\Models\Calculation;
use App\Modules\Catalog\Database\Seeders\CalculatorSeeder;
use App\Modules\Catalog\Models\Calculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CalculationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_inputs_are_accepted_but_unbounded_numeric_strings_are_rejected(): void
    {
        $this->seed(CalculatorSeeder::class);
        foreach ([
            ['mortgage', ['amount' => '2000000,50', 'down_payment' => '25,5', 'years' => 20, 'salary' => '40000,50']],
            ['auto', ['amount' => '100000,50', 'down_payment' => '0', 'years' => 1]],
            ['consumer', ['amount' => '100000,50', 'years' => 1]],
            ['pension', ['amount' => '3000,50', 'years' => 3, 'gender' => 'male']],
            ['deposit', ['amount' => '50000,50', 'months' => 3]],
        ] as [$product, $input]) {
            $this->postJson('/calculations/'.$product, $input)->assertCreated();
        }
        $this->postJson('/calculations/deposit', ['amount' => str_repeat('9', 65), 'months' => 3])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson('/calculations/mortgage', ['amount' => '2000000', 'down_payment' => '10.'.str_repeat('1', 1000), 'years' => 20])->assertUnprocessable()->assertJsonValidationErrors('down_payment');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CalculatorSeeder::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_a_mortgage_is_calculated_stored_and_presented_without_decimal_money(): void
    {
        $response = $this->postJson('/calculations/mortgage', ['amount' => '2 000 000', 'years' => 20, 'down_payment' => 25, 'salary' => '40000']);
        $response->assertCreated()->assertJsonPath('summary.principal', '1 500 000 ₽')
            ->assertJsonPath('summary.monthly_payment', '14 080 ₽')
            ->assertJsonPath('summary.recommended_income', '35 200 ₽')
            ->assertJsonCount(240, 'rows');
        self::assertSame(1, Calculation::query()->count());
        $stored = Calculation::query()->firstOrFail();
        self::assertSame('mortgage', $stored->product_code);
        self::assertSame('2000000', $stored->payload['inputs']['amount']);
        self::assertNotSame('14080', $stored->payload['result']['summary']['monthly_payment']);
    }

    public function test_a_salary_below_the_recommendation_does_not_block_the_result(): void
    {
        $this->postJson('/calculations/consumer', ['amount' => 300000, 'years' => 3, 'salary' => 10000])
            ->assertCreated()->assertJsonPath('income_comparison.sufficient', false)
            ->assertJsonPath('summary.monthly_payment', '10 326 ₽');
        $this->postJson('/calculations/consumer', ['amount' => 300000, 'years' => 3])
            ->assertCreated()->assertJsonPath('income_comparison', null);
    }

    public function test_both_mortgage_term_boundaries_are_available_but_fractional_years_are_not(): void
    {
        foreach ([1, 30] as $years) {
            $this->postJson('/calculations/mortgage', ['amount' => 100000, 'years' => $years, 'down_payment' => 10])->assertCreated();
        }
        foreach ([0, 31, '1.5'] as $years) {
            $this->postJson('/calculations/mortgage', ['amount' => 100000, 'years' => $years, 'down_payment' => 10])
                ->assertUnprocessable()->assertJsonValidationErrors('years');
        }
    }

    public function test_loan_limits_and_fixed_rate_cannot_be_bypassed_by_the_client(): void
    {
        $this->postJson('/calculations/mortgage', ['amount' => 99999, 'years' => 20, 'down_payment' => 10])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson('/calculations/mortgage', ['amount' => 100000, 'years' => 20, 'down_payment' => 9])
            ->assertUnprocessable()->assertJsonValidationErrors('down_payment');
        $this->postJson('/calculations/consumer', ['amount' => 6000001, 'years' => 7])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson('/calculations/consumer', ['amount' => 300000, 'years' => 3, 'rate' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('rate');
        self::assertSame(0, Calculation::query()->count());
    }

    public function test_auto_accepts_zero_down_payment_and_rejects_terms_over_seven_years(): void
    {
        $this->postJson('/calculations/auto', ['amount' => 100000, 'years' => 7, 'down_payment' => 0])
            ->assertCreated()->assertJsonPath('summary.principal', '100 000 ₽');
        $this->postJson('/calculations/auto', ['amount' => 100000, 'years' => 8, 'down_payment' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('years');
    }

    public function test_short_pension_program_still_returns_all_payments_and_rounds_the_half_ruble_up(): void
    {
        $this->postJson('/calculations/pension', ['amount' => 3000, 'years' => 3, 'gender' => 'male'])
            ->assertCreated()->assertJsonPath('summary.balance_at_retirement', '216 000 ₽')
            ->assertJsonPath('rows.0.payment', '905 ₽')->assertJsonCount(240, 'rows');
        $this->postJson('/calculations/pension', ['amount' => 3000, 'years' => 3])
            ->assertUnprocessable()->assertJsonValidationErrors('gender');
    }

    public function test_deposit_uses_bank_date_and_rejects_multiple_periods(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2025-01-01T21:30:00Z'));
        $this->postJson('/calculations/deposit', ['amount' => 50000, 'months' => 3])
            ->assertCreated()->assertJsonPath('summary.opened_at', '02.01.2025')
            ->assertJsonPath('summary.matures_at', '02.04.2025');
        $this->postJson('/calculations/deposit', ['amount' => 50000, 'months' => [3, 6]])
            ->assertUnprocessable()->assertJsonValidationErrors('months');
    }

    public function test_internal_calculator_records_cannot_be_used_as_public_products(): void
    {
        Calculator::query()->create(['name' => 'Внутренняя запись', 'type' => 'deposit', 'rate' => '7', 'parameters' => []]);
        $this->postJson('/calculations/internal', ['amount' => 50000, 'months' => 3])->assertNotFound();
    }
}
