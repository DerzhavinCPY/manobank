<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Modules\Calculations\Rules\ExistingCalculation;
use App\Modules\Calculations\Services\CalculationHistory;
use App\Modules\Reports\Services\SpreadsheetReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ExportController
{
    public function __invoke(Request $request, CalculationHistory $history, SpreadsheetReport $report): Response
    {
        $selection = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['bail', 'required', 'uuid', 'distinct', new ExistingCalculation],
        ], [
            'selected.required' => 'Отметьте расчёты для выгрузки.',
        ]);

        return response($report->bytes($history->snapshots($selection['selected'])), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="manobank-calculations.xlsx"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
