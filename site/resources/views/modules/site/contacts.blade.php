@extends('site::layout')
@section('content')
<p class="breadcrumbs"><a href="{{ route('site.home') }}">Главная</a> / Контакты</p>
<h1 class="page-heading">Контакты</h1>
<div class="contact-summary">
    <section><h2>Юридический и почтовый адрес</h2><p>@foreach($contacts['address_lines'] as $line)<span class="address-line">{{ $line }}</span>@endforeach</p></section>
    <section><h2>Телефоны</h2>@foreach($contacts['phones'] as $phone)<p><a href="tel:{{ preg_replace('/[^+0-9]/', '', $phone) }}">{{ $phone }}</a></p>@endforeach</section>
    <section><h2>Электронная почта</h2><p><a href="mailto:{{ $contacts['email'] }}">{{ $contacts['email'] }}</a></p><p>{{ $contacts['support'] }}</p></section>
</div>
<div class="contacts-grid"><section><h2>Отделение МаноБанка</h2><img class="entrance-image" src="{{ route('site.media', 'entrance') }}" alt="Вход в отделение МаноБанка с сине-оранжевой вывеской" width="1536" height="1024"></section><section class="map-section"><h2>Как добраться</h2><img src="{{ route('site.media', 'map') }}" alt="Карта проезда к отделению: Ижевск, улица Ленина, 112" loading="lazy"></section></div>
@endsection
