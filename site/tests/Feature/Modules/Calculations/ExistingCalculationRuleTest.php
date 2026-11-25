<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Calculations;

use App\Modules\Calculations\Rules\ExistingCalculation;
use App\Modules\Catalog\Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class ExistingCalculationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_saved_calculation_can_pass_the_public_validation_rule(): void
    {
        $this->seed(CalculatorSeeder::class);
        $id = $this->postJson('/calculations/consumer', ['amount' => 100000, 'years' => 1])
            ->assertCreated()->json('id');

        foreach ([$id => true, '00000000-0000-4000-8000-000000000000' => false] as $value => $expected) {
            $validator = Validator::make(['calculation' => $value], ['calculation' => [new ExistingCalculation]]);
            self::assertSame($expected, $validator->passes());
        }
    }
}
