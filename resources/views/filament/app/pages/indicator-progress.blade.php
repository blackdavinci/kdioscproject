<x-filament-panels::page>
    <div class="mb-4 max-w-xl">
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="indicatorId">
                @foreach ($this->indicatorOptions() as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    @php($indicator = $this->indicator())
    @php($rows = $this->rows())

    @if (! $indicator)
        <x-filament::section><p class="text-sm text-gray-500 dark:text-gray-400">Aucun indicateur.</p></x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">{{ $indicator->label }}</x-slot>
            <x-slot name="description">
                Unité : {{ $indicator->unit ?? '—' }} · Baseline : {{ $indicator->baseline_value ?? '—' }} · {{ $indicator->direction->getLabel() }}
            </x-slot>

            @if (count($rows) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucune cible ni valeur saisie pour cet indicateur.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="py-2">Période</th>
                                <th>Cible</th>
                                <th>Réalisé</th>
                                <th class="w-1/2">Atteinte</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php($pct = $row['percent'])
                                @php($color = $pct === null ? 'bg-gray-300' : ($pct >= 80 ? 'bg-success-500' : ($pct >= 50 ? 'bg-warning-500' : 'bg-danger-500')))
                                <tr class="border-t border-gray-100 dark:border-white/10">
                                    <td class="py-2 font-medium">{{ $row['label'] }}</td>
                                    <td>{{ $row['target'] ?? '—' }}</td>
                                    <td>{{ $row['realized'] ?? '—' }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="relative h-3 flex-1 rounded bg-gray-100 dark:bg-white/10">
                                                @if ($pct !== null)
                                                    <div class="absolute inset-y-0 left-0 rounded {{ $color }}" style="width: {{ $pct }}%"></div>
                                                @endif
                                            </div>
                                            <span class="w-14 text-right text-xs {{ $pct === null ? 'text-gray-400' : '' }}">
                                                {{ $row['attainment'] !== null ? round($row['attainment'] * 100).' %' : '—' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
