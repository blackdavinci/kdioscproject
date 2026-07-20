@php($groups = \App\Filament\App\Resources\Indicators\Support\ValueDisaggregation::breakdown($record))

<div class="space-y-4 text-sm">
    <div class="flex items-baseline gap-2">
        <span class="text-gray-500 dark:text-gray-400">Période {{ $record->period_label }} — valeur totale :</span>
        <span class="font-semibold">{{ $record->value }}</span>
    </div>

    @forelse ($groups as $group)
        <div>
            <div class="mb-1 font-medium">{{ $group['dimension'] }}</div>
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                @foreach ($group['rows'] as $row)
                    <div class="flex justify-between border-b border-gray-100 px-3 py-1.5 last:border-0 dark:border-white/5">
                        <span>{{ $row['label'] }}</span>
                        <span class="font-medium">{{ $row['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400">Aucune ventilation saisie pour cette valeur.</p>
    @endforelse
</div>
