<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — МаноБанк</title>
    <meta name="description" content="{{ isset($product) ? $product->name.' — '.$product->subtitle.' Расчёт МаноБанка.' : 'Калькуляторы МаноБанка: ипотека, автокредит, потребительский кредит, пенсионные накопления и вклад повседневный.' }}">
    <link rel="canonical" href="{{ app(\App\Modules\Site\Services\SearchMetadata::class)->canonical(request()->path()) }}">
    @if($title === 'Технические работы')<meta name="robots" content="noindex, follow">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @if($openAccount ?? false) data-open-account @endif>
<a class="skip-link" href="#main">К содержимому</a>
@include('site::partials.header')
<main id="main" class="container page-main" tabindex="-1">@yield('content')</main>
@include('site::partials.footer')
@include('site::partials.dialogs')
</body>
</html>
