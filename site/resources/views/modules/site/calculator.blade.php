@extends('site::layout')
@section('content')
<p class="breadcrumbs"><a href="{{ route('site.home') }}">Главная</a> / <a href="{{ route('site.catalog') }}">Калькулятор</a> / {{ $product->name }}</p>
<div class="calculator-heading calculator-heading-{{ $product->type }}"><h1>{{ $product->name }}</h1><span class="rate-badge">{{ str_replace('.', ',', $product->rate) }}% годовых</span><p>{{ $product->subtitle }}</p></div>
<form class="calculator-panel calculator-{{ $product->type }}" data-calculator data-product="{{ $product->code }}" action="{{ route('calculations.store', $product->code) }}" method="post" novalidate>
    @csrf
    @if($product->type === 'pension')
        <div class="field field-years"><label for="years">Осталось до начала выплат</label><input id="years" name="years" type="text" inputmode="numeric" placeholder="Сколько лет до пенсии?" data-years-input>@include('site::partials.range', ['min' => 0, 'max' => 50])<fieldset class="gender-choice"><legend>Возраст начала выплат</legend><label><input type="radio" name="gender" value="male">Мужчина — 65 лет</label><label><input type="radio" name="gender" value="female">Женщина — 60 лет</label></fieldset><p class="field-error" data-error="gender"></p></div>
        <div class="field field-amount"><label for="amount">Ежемесячный взнос</label>@include('site::partials.amount-input', ['placeholder' => 'Внесите сумму'])<p class="field-hint">От 3 000 до 20 000 ₽ в месяц</p><p class="field-error" data-error="amount"></p></div>
    @elseif($product->type === 'deposit')
        <div class="field field-amount"><label for="amount">Сумма вклада</label>@include('site::partials.amount-input', ['placeholder' => 'Минимальная сумма — 50 000 ₽'])<p class="field-error" data-error="amount"></p></div>
        <fieldset class="term-choice"><legend>Срок вклада</legend>@foreach([3, 6, 9, 12] as $month)<label><input type="radio" name="months" value="{{ $month }}">{{ $month }} мес.</label>@endforeach<p class="field-error" data-error="months"></p></fieldset>
        <div class="deposit-conditions"><h2>Условия вклада</h2><p>Без капитализации</p><p>Проценты на пополнения — со следующих суток.</p></div>
    @else
        <div class="field field-amount"><label for="amount">{{ $product->type === 'mortgage' ? 'Стоимость недвижимости' : ($product->type === 'auto' ? 'Стоимость автомобиля' : 'Сумма кредита') }}</label>@include('site::partials.amount-input', ['placeholder' => $product->type === 'mortgage' ? 'Стоимость недвижимости: 100 000–15 000 000 ₽' : ($product->type === 'auto' ? 'Стоимость автомобиля: 100 000–6 000 000 ₽' : 'От 100 000 до 6 000 000 ₽')])<p class="field-error" data-error="amount"></p></div>
        <div class="field field-years"><label for="years">Срок кредита</label><input id="years" name="years" type="text" inputmode="numeric" placeholder="{{ $product->type === 'mortgage' ? 'От 1 до 30 лет' : 'От 1 до 7 лет' }}" data-years-input>@include('site::partials.range', ['min' => 1, 'max' => $product->parameters['max_years']])</div>
        @if($product->type !== 'consumer')<div class="field field-down"><label for="down_payment">Первоначальный взнос, %</label><div class="suffix-input"><input id="down_payment" name="down_payment" type="text" inputmode="decimal" placeholder="{{ $product->type === 'mortgage' ? 'От 10%' : 'От 0%' }}" data-percent-input><span data-percent-suffix hidden>%</span></div><p class="field-error" data-error="down_payment"></p><p class="field-hint">Первоначальный взнос: <span data-down-amount>0 ₽</span></p></div>@endif
        <div class="field field-salary"><label for="salary">Ваша заработная плата</label><input id="salary" name="salary" type="text" inputmode="numeric" placeholder="Необязательное поле" data-money-input><p class="field-error" data-error="salary"></p></div>
    @endif
    <div class="calculator-actions"><button class="button primary" type="submit">Рассчитать</button><button class="button" type="reset">Сбросить</button></div>
    <p class="calculation-error field-error" role="alert" data-calculation-error hidden></p>
</form>
@php
    $labels = match($product->type) {
        'pension' => ['1-й месяц', '12-й месяц', '60-й месяц', '200-й месяц', '240-й месяц'],
        'deposit' => ['Сумма вклада', 'Срок вклада', 'Процентный доход', 'К получению в конце срока'],
        default => ['Срок кредита', 'Сумма кредита', 'Ежемесячный платёж', 'Рекомендуемая зарплата']
    };
@endphp
<section class="calculation-result {{ $product->type === 'pension' ? 'pension-result' : '' }}" aria-live="polite" aria-atomic="true"><h2>{{ $product->type === 'pension' ? 'Будущие ежемесячные выплаты' : ($product->type === 'deposit' ? 'Результат расчёта' : 'Ваш расчёт') }}</h2>@if($product->type === 'pension')<p class="pension-balance">Сумма к началу выплат: <strong data-retirement-balance data-zero-value="0 ₽">0 ₽</strong></p>@endif<div class="metrics">@foreach($labels as $label)@php($zero = $label === 'Срок кредита' ? '0 лет' : ($label === 'Срок вклада' ? '0 мес.' : '0 ₽'))<div class="metric"><span data-metric-label>{{ $label }}</span><strong data-metric-value data-zero-value="{{ $zero }}">{{ $zero }}</strong></div>@endforeach</div><p data-income-comparison hidden></p></section>
<div class="result-actions"><button class="button email-button" type="button" data-email-open disabled>Отправить полный расчёт на Email</button>@if($product->type === 'pension')<a class="button primary program-button" href="{{ route('site.pension-program') }}">Подробнее о госпрограмме</a>@endif @if($product->type === 'deposit')<p class="deposit-note">При досрочном закрытии процентный доход теряется.</p>@endif</div>
@endsection
