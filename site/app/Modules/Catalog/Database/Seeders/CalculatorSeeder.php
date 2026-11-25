<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\Calculator;
use App\Modules\Catalog\Services\ProductCatalog;
use Illuminate\Database\Seeder;

final class CalculatorSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductCatalog::definitions() as $code => $definition) {
            if (! Calculator::query()->where('code', $code)->exists()) {
                (new Calculator)->forceFill($definition + [
                    'code' => $code,
                    'is_public' => true,
                    'sort_order' => array_search($code, ProductCatalog::CODES, true),
                ])->save();
            }
        }
    }
}
