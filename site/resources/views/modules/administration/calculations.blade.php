@extends('administration::layout', ['title' => 'История расчётов'])
@section('content')
<form action="{{ route('admin.calculations.export') }}" method="get" data-calculation-export>
    <div class="admin-title">
        <h1>История расчётов</h1>
        <button class="button" type="submit" disabled>Выгрузить XLSX</button>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th scope="col">Выбор</th><th scope="col">Продукт</th><th scope="col">Дата расчёта</th><th scope="col">Результат</th></tr></thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td><input class="calculation-selection" type="checkbox" name="selected[]" value="{{ $record->public_id }}" aria-label="Выбрать расчёт {{ $record->public_id }}"></td>
                    <td>{{ $record->product_name }}</td>
                    <td>{{ $record->created_at->timezone('Europe/Samara')->format('d.m.Y H:i') }}</td>
                    <td><a href="{{ route('admin.calculations.show', $record->public_id) }}">Открыть расчёт</a></td>
                </tr>
                @empty
                <tr><td colspan="4">Сохранённых расчётов пока нет.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>
{{ $records->links() }}
@endsection
