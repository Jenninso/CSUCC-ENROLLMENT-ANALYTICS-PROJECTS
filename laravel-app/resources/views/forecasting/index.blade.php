@extends('layouts.app')
@section('title', 'Forecasting')

@section('content')
<p class="text-sm text-gray-500 mb-4">The system compares naive, moving-average, linear regression, and exponential-smoothing models and selects the lowest MAE. Complex models are not used when simpler ones perform better.</p>

<form method="POST" action="{{ route('forecasting.store') }}" class="bg-white rounded-lg shadow-sm p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
    @csrf
    <select name="program_id" class="px-3 py-2 border rounded-lg text-sm">
        <option value="">Institution overall</option>
        @foreach($programs as $program)
            <option value="{{ $program->id }}">{{ $program->program_code }}</option>
        @endforeach
    </select>
    <select name="college_id" class="px-3 py-2 border rounded-lg text-sm">
        <option value="">No college filter</option>
        @foreach($colleges as $college)
            <option value="{{ $college->id }}">{{ $college->college_code }}</option>
        @endforeach
    </select>
    <select name="horizon" class="px-3 py-2 border rounded-lg text-sm">
        <option value="1">Next academic year</option>
        <option value="2">2-year horizon</option>
        <option value="3">3-year horizon</option>
    </select>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Run forecast</button>
</form>

@if(!empty($result))
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold mb-2">{{ $result['ok'] ? 'Selected model: '.$result['best']['name'] : 'Forecast unavailable' }}</h3>
        @if($result['ok'])
            <p class="text-sm text-gray-600 mb-4">Scope: {{ $result['scope'] }}. MAE {{ $result['best']['mae'] }}, RMSE {{ $result['best']['rmse'] }}, MAPE {{ $result['best']['mape'] }}%.</p>
            <canvas id="forecastChart" height="160"></canvas>
        @else
            <p class="text-sm text-amber-700">{{ $result['message'] }}</p>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <h3 class="font-semibold mb-3">Model comparison</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="py-2">Model</th><th>MAE</th><th>RMSE</th><th>MAPE</th></tr></thead>
            <tbody>
            @foreach($result['models'] ?? [] as $model)
                <tr class="border-t"><td class="py-2">{{ $model['name'] }}</td><td>{{ $model['mae'] }}</td><td>{{ $model['rmse'] }}</td><td>{{ $model['mape'] }}%</td></tr>
            @endforeach
            </tbody>
        </table>
        @if($result['ok'] ?? false)
            <ul class="mt-4 text-sm space-y-1">
                @foreach($result['forecasts'] as $row)
                    <li>{{ $row['period'] }}: {{ number_format($row['predicted']) }} ({{ number_format($row['lower']) }}–{{ number_format($row['upper']) }})</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow-sm p-5">
    <h3 class="font-semibold mb-3">Saved forecasts</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-gray-500"><th class="py-2">Period</th><th>Scope</th><th>Predicted</th><th>Interval</th><th>Model</th><th>MAE</th></tr></thead>
        <tbody>
        @forelse($history as $item)
            <tr class="border-t">
                <td class="py-2">{{ $item->forecast_period }}</td>
                <td>{{ $item->program?->program_code ?? $item->college?->college_code ?? 'Institution' }}</td>
                <td>{{ number_format($item->predicted_enrollment) }}</td>
                <td>{{ $item->lower_bound }}–{{ $item->upper_bound }}</td>
                <td>{{ $item->model_used }}</td>
                <td>{{ $item->accuracy_metric }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-6 text-center text-gray-400">No forecasts stored yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
@if(!empty($result['ok']))
<script>
    const labels = @json($result['series']['labels'] ?? []);
    const hist = @json($result['series']['values'] ?? []);
    const f = @json($result['forecasts'] ?? []);
    const forecastLabels = f.map(x => x.period);
    const predicted = f.map(x => x.predicted);
    new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        data: {
            labels: labels.concat(forecastLabels),
            datasets: [
                { label: 'Historical', data: hist.concat(Array(forecastLabels.length).fill(null)), borderColor: '#6366f1', tension: 0.2 },
                { label: 'Forecast', data: Array(hist.length - 1).fill(null).concat([hist[hist.length-1]]).concat(predicted), borderColor: '#f59e0b', borderDash: [6,4], tension: 0.2 }
            ]
        },
        options: { responsive: true }
    });
</script>
@endif
@endpush
