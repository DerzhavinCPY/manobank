<?php

declare(strict_types=1);

namespace App\Modules\Administration\Http\Controllers;

use App\Modules\Calculations\Services\CalculationHistory;
use App\Modules\Calculations\Services\ResultPresenter;
use App\Modules\Catalog\Services\CalculatorDrafts;
use App\Modules\Catalog\Services\ProductCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdministrationController
{
    public function dashboard(): View
    {
        return view('administration::dashboard');
    }

    public function calculators(CalculatorDrafts $drafts): View
    {
        return view('administration::calculators', ['records' => $drafts->listing()]);
    }

    public function create(): View
    {
        return view('administration::calculator-form', ['record' => null, 'definitions' => ProductCatalog::definitions()]);
    }

    public function edit(int $id, CalculatorDrafts $drafts): View
    {
        return view('administration::calculator-form', ['record' => $drafts->editable($id), 'definitions' => ProductCatalog::definitions()]);
    }

    public function store(Request $request, CalculatorDrafts $drafts): RedirectResponse
    {
        $drafts->save($request->all());

        return redirect()->route('admin.calculators')->with('status', 'Внутренний черновик сохранён.');
    }

    public function update(Request $request, int $id, CalculatorDrafts $drafts): RedirectResponse
    {
        $drafts->save($request->all(), $id);

        return redirect()->route('admin.calculators')->with('status', 'Изменения сохранены.');
    }

    public function destroy(int $id, CalculatorDrafts $drafts): RedirectResponse
    {
        $drafts->delete($id);

        return redirect()->route('admin.calculators')->with('status', 'Внутренний черновик удалён.');
    }

    public function calculations(CalculationHistory $history): View
    {
        return view('administration::calculations', ['records' => $history->listing()]);
    }

    public function calculation(string $id, CalculationHistory $history, ResultPresenter $presenter): View
    {
        return view('administration::calculation', ['result' => $presenter->present($history->snapshot($id))]);
    }
}
