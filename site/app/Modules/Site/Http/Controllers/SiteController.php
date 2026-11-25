<?php

declare(strict_types=1);

namespace App\Modules\Site\Http\Controllers;

use App\Modules\Catalog\Services\ProductCatalog;
use App\Modules\Site\Services\BankDetails;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SiteController
{
    public function catalog(Request $request, ProductCatalog $catalog): View
    {
        return view('site::catalog', [
            'title' => 'Калькулятор', 'active' => 'calculator',
            'products' => $catalog->publicProducts(), 'openAccount' => $request->is('account'),
        ]);
    }

    public function calculator(string $product, ProductCatalog $catalog): View
    {
        $calculator = $catalog->publicProduct($product);

        return view('site::calculator', ['title' => $calculator->name, 'active' => 'calculator', 'product' => $calculator]);
    }

    public function maintenance(Request $request): View
    {
        $active = match ($request->path()) {
            'home' => 'home', 'products' => 'products', 'pension-program' => 'calculator', default => null,
        };

        $breadcrumb = match ($request->path()) {
            'home' => 'Главная', 'products' => 'Главная / Наши продукты',
            'support' => 'Главная / Поддержка / supp.manobank@mail.ru',
            'privacy' => 'Главная / Документы / Политика обработки персональных данных',
            'terms' => 'Главная / Документы / Пользовательское соглашение',
            'pension-program' => 'Главная / Калькулятор / Пенсионные накопления / Подробнее о госпрограмме',
        };

        return view('site::maintenance', ['title' => 'Технические работы', 'active' => $active, 'breadcrumb' => $breadcrumb]);
    }

    public function contacts(): View
    {
        return view('site::contacts', ['title' => 'Контакты', 'active' => 'contacts', 'contacts' => BankDetails::contacts()]);
    }

    public function requisites(): View
    {
        return view('site::requisites', ['title' => 'Реквизиты', 'active' => null, 'requisites' => BankDetails::requisites()]);
    }
}
