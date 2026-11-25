@extends('administration::layout')
@section('content')<h1>Администрирование</h1><div class="admin-cards"><a href="{{ route('admin.calculators') }}"><h2>Калькуляторы</h2><p>Управление внутренними черновиками. Публичные продукты защищены.</p></a><a href="{{ route('admin.calculations') }}"><h2>История расчётов</h2><p>Сохранённые исходные данные и подробные результаты.</p></a></div>@endsection
