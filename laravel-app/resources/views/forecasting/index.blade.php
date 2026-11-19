@extends('layouts.app')
@section('title', 'Forecasting')
@section('subtitle', 'Blue is recorded enrollment. Orange is the Random Forest projection for the years that have not happened yet.')

@section('content')

<form method="POST" action="{{ route('forecasting.store') }}" class="bg-white rounded-lg shadow-sm p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
    @csrf
    <select name="program_id" class="px-3 py-2 border rounded-lg text-sm">
        <option value="">Institution overall</option>
        @foreach($programs as $program)
            <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->program_code }}</option>
        @endforeach
    </select>
    <select name="college_id" class="px-3 py-2 border rounded-lg text-sm">
        @foreach($colleges as $college)
            <option value="{{ $college->id }}" @selected((string) ($filters['college_id'] ?? $college->id) === (string) $college->id)>{{ $college->college_code }}</option>
        @endforeach
    </select>
    <select name="horizon" class="px-3 py-2 border rounded-lg text-sm">
        <option value="1" @selected((int) ($filters['horizon'] ?? 1) === 1)>Next academic year</option>
        <option value="2" @selected((int) ($filters['horizon'] ?? 1) === 2)>2-year horizon</option>
        <option value="3" @selected((int) ($filters['horizon'] ?? 1) === 3)>3-year horizon</option>
    </select>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Run forecast</button>
</form>

@if(!empty($result))
@php
    $historyValues = array_values($result['series']['values'] ?? []);
    $historyLabels = array_values($result['series']['labels'] ?? []);
    $latestValue = $historyValues === [] ? null : (int) $historyValues[array_key_last($historyValues)];
    $latestLabel = $historyLabels === [] ? '' : (string) $historyLabels[array_key_last($historyLabels)];
    $priorValue = count($historyValues) >= 2 ? (int) $historyValues[count($historyValues) - 2] : null;
    $priorLabel = count($historyLabels) >= 2 ? (string) $historyLabels[count($historyLabels) - 2] : '';
    $sharpDrop = $priorValue && $latestValue !== null && $latestValue < ($priorValue * 0.7);
@endphp
<div class="space-y-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h3 class="font-semibold">{{ $result['ok'] ? $result['scope'].' enrollment' : 'Forecast unavailable' }}</h3>
                @if($result['ok'])
                    <p class="text-sm text-gray-600 mt-1">Random Forest reads the blue years, then draws the orange years. The orange numbers have not happened yet.</p>
                @endif
            </div>
            @if($result['ok'])
                <div class="flex flex-wrap gap-3 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1"><span class="h-2.5 w-6 rounded-full bg-indigo-600"></span> Recorded</span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1"><span class="h-0.5 w-6 border-t-2 border-dashed border-amber-500"></span> Predicted</span>
                </div>
            @endif
        </div>
        @if($result['ok'])
            <div class="relative h-96">
                <canvas id="forecastChart"></canvas>
            </div>
            @if($sharpDrop)
                <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $latestLabel }} is recorded at {{ number_format($latestValue) }}, down from {{ number_format($priorValue) }} in {{ $priorLabel }}. The prediction starts from {{ number_format($latestValue) }} and does not jump back to the earlier peak. Check that {{ $latestLabel }} is fully encoded before using it for a plan.</p>
            @else
                <p class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">The prediction starts from the latest recorded year, {{ $latestLabel }} ({{ number_format($latestValue) }} students), and carries the usual year-to-year change forward.</p>
            @endif
        @else
            <p class="text-sm text-amber-700">{{ $result['message'] }}</p>
        @endif
    </div>

    @if($result['ok'] ?? false)
        <div class="grid grid-cols-1 md:grid-cols-{{ min(count($result['forecasts']), 3) }} gap-4">
            @foreach($result['forecasts'] as $row)
                <div class="bg-white rounded-lg shadow-sm p-5 border-t-4 border-amber-400">
                    <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Predicted · {{ $row['period'] }}</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($row['predicted']) }}</p>
                    <p class="text-sm text-gray-500">students</p>
                    <p class="mt-3 text-sm text-gray-600">Working figure: {{ number_format($row['predicted']) }}. Because the last check was far off, the unsure range is {{ number_format($row['lower']) }} to {{ number_format($row['upper']) }}.</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">MAE · average miss</p>
                <p class="mt-2 text-2xl font-semibold">{{ number_format($result['best']['mae'], 0) }}</p>
                <p class="mt-2 text-sm text-gray-600">Students off when the latest recorded year was hidden and then predicted. Lower is closer.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">RMSE · large-miss check</p>
                <p class="mt-2 text-2xl font-semibold">{{ number_format($result['best']['rmse'], 0) }}</p>
                <p class="mt-2 text-sm text-gray-600">Same held-out year, with a big miss weighed more. It matches MAE here because only one year was checked.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">MAPE · miss as a percent</p>
                <p class="mt-2 text-2xl font-semibold">{{ $result['best']['mape'] }}%</p>
                <p class="mt-2 text-sm text-gray-600">The miss divided by the recorded headcount of the hidden year. @if($result['best']['mape'] >= 100) The error is larger than that year's actual count. @else The error is a fraction of that year's actual count. @endif</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Where the {{ number_format($result['training']['rows']) }} training rows came from</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="rounded-lg bg-indigo-50 px-4 py-3">
                    <p class="font-medium text-indigo-900">{{ $result['training']['real_periods'] }} recorded years</p>
                    <p class="mt-1 text-indigo-800">These are the blue points. They are the real enrollment totals for this scope.</p>
                </div>
                <div class="rounded-lg bg-amber-50 px-4 py-3">
                    <p class="font-medium text-amber-900">{{ number_format($result['training']['series']) }} practice series</p>
                    <p class="mt-1 text-amber-800">Each copy keeps those recorded years, then extends them by the usual yearly change. The goal was {{ number_format($result['training']['goal']) }} rows.</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="font-medium text-gray-900">Enrollment records stay as they are</p>
                    <p class="mt-1 text-gray-600">The practice rows train Random Forest only. They are not new students and they are not added to the database.</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endif

<div class="bg-white rounded-lg shadow-sm p-5">
    <h3 class="font-semibold">Saved forecasts</h3>
    <p class="text-sm text-gray-500 mb-3">Each row is one forecast already stored. Predicted is the working figure. Interval is the unsure range around it.</p>
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
    const historyLabels = @json($result['series']['labels'] ?? []);
    const historyValues = @json($result['series']['values'] ?? []);
    const forecasts = @json($result['forecasts'] ?? []);
    const labels = historyLabels.concat(forecasts.map(row => row.period));
    const historyData = historyValues.concat(forecasts.map(() => null));
    const forecastData = Array(Math.max(historyValues.length - 1, 0)).fill(null)
        .concat(historyValues.length ? [historyValues[historyValues.length - 1]] : [])
        .concat(forecasts.map(row => row.predicted));
    const forecastStart = Math.max(historyValues.length - 1, 0);

    const forecastBand = {
        id: 'forecastBand',
        beforeDatasetsDraw(chart) {
            const { ctx, chartArea, scales } = chart;
            if (!chartArea || !scales.x) return;
            const x = scales.x.getPixelForValue(forecastStart);
            ctx.save();
            ctx.fillStyle = 'rgba(245, 158, 11, 0.10)';
            ctx.fillRect(x, chartArea.top, chartArea.right - x, chartArea.bottom - chartArea.top);
            ctx.fillStyle = '#b45309';
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText('Predicted years', x + 12, chartArea.top + 18);
            ctx.restore();
        }
    };

    const pointValues = {
        id: 'pointValues',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            ctx.save();
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'center';
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                chart.getDatasetMeta(datasetIndex).data.forEach((point, index) => {
                    const value = dataset.data[index];
                    if (value == null || (datasetIndex === 1 && index < historyValues.length)) return;
                    ctx.fillStyle = datasetIndex === 0 ? '#3730a3' : '#b45309';
                    ctx.fillText(Number(value).toLocaleString(), point.x, point.y - 12);
                });
            });
            ctx.restore();
        }
    };

    new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        plugins: [forecastBand, pointValues],
        data: {
            labels,
            datasets: [
                {
                    label: 'Recorded enrollment',
                    data: historyData,
                    borderColor: '#4f46e5',
                    backgroundColor: '#4f46e5',
                    pointRadius: 5,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                    tension: 0.15,
                    spanGaps: false
                },
                {
                    label: 'Predicted enrollment',
                    data: forecastData,
                    borderColor: '#d97706',
                    backgroundColor: '#d97706',
                    pointRadius: 5,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                    borderDash: [6, 4],
                    tension: 0.15
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 28 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(context) {
                            if (context.parsed.y == null) return null;
                            const kind = context.datasetIndex === 0 ? 'Recorded' : 'Predicted';
                            return kind + ': ' + Number(context.parsed.y).toLocaleString() + ' students';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    title: { display: true, text: 'Academic year' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { callback: value => Number(value).toLocaleString() },
                    title: { display: true, text: 'Students' }
                }
            }
        }
    });
</script>
@endif
@endpush
