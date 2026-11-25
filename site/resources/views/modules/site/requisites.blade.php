@extends('site::layout')
@section('content')
<p class="breadcrumbs"><a href="{{ route('site.home') }}">Главная</a> / Реквизиты</p>
<h1 class="page-heading">Реквизиты ПАО «МаноБанк»</h1>
<p class="legal-name">Публичное акционерное общество «МаноБанк»</p>
<dl class="requisites">@foreach($requisites as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl>
@endsection
