@extends('site::layout')
@section('content')
<p class="breadcrumbs"><a href="{{ route('site.home') }}">Главная</a> / Калькулятор</p>
<section class="catalog-intro"><h1>Какой расчёт вам нужен?</h1><p>Выберите наш продукт, чтобы перейти к расчету</p></section>
<div class="product-grid">
    @foreach($products as $product)
        <a class="product-card" href="{{ route('site.calculator', $product->code) }}" data-product-card>
            @include('site::partials.product-icon', ['code' => $product->code])
            <h2>{{ $product->name }}</h2><span class="card-arrow" aria-hidden="true">→</span>
        </a>
    @endforeach
</div>
<p class="catalog-caption">Ипотека, кредиты и накопления — в одном разделе.</p>
@endsection
