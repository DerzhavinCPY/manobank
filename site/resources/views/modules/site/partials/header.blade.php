<header class="site-header container">
    <a class="brand" href="{{ route('site.start') }}" aria-label="МаноБанк — начало"><span>Мано</span><span>Банк</span></a>
    <nav class="main-nav" aria-label="Главное меню">
        @foreach(['home' => ['Главная', 'site.home'], 'products' => ['Наши продукты', 'site.products'], 'calculator' => ['Калькулятор', 'site.catalog'], 'contacts' => ['Контакты', 'site.contacts'], 'account' => ['Личный кабинет', 'site.account']] as $key => [$label, $route])
            <a href="{{ route($route) }}" data-nav="{{ $key }}" class="nav-link {{ ($active ?? null) === $key ? 'is-active' : '' }}" @if(($active ?? null) === $key) aria-current="page" @endif @if($key === 'account') data-account-open @endif>{{ $label }}</a>
        @endforeach
    </nav>
</header>
