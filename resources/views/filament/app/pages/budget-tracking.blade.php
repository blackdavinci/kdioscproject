<x-filament-panels::page>
    <div class="mb-4 max-w-xl">
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="projectId">
                @foreach ($this->projectOptions() as $id => $title)
                    <option value="{{ $id }}">{{ $title }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    @php($lines = $this->lines())
    @php($totals = $this->totals())

    <x-filament::section>
        @if ($lines->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune ligne budgétaire pour ce projet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-2">Rubrique</th>
                            <th>Ligne</th>
                            <th class="text-right">Budget</th>
                            <th class="text-right">Engagé</th>
                            <th class="text-right">Dépensé</th>
                            <th class="text-right">Disponible</th>
                            <th>Consommation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            @php($rate = $line->consumptionRate())
                            @php($pct = $rate !== null ? round($rate * 100) : null)
                            @php($color = $line->isOverspent() ? 'bg-danger-500' : ($line->isOverThreshold() ? 'bg-warning-500' : 'bg-success-500'))
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $line->category?->name ?? '—' }}</td>
                                <td class="font-medium">{{ $line->label }}</td>
                                <td class="text-right">{{ number_format($line->amount_gnf, 0, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($line->committed(), 0, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($line->spent(), 0, ',', ' ') }}</td>
                                <td class="text-right {{ $line->isOverspent() ? 'text-danger-600 font-semibold' : '' }}">{{ number_format($line->available(), 0, ',', ' ') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="relative h-2.5 w-24 rounded bg-gray-100 dark:bg-white/10">
                                            @if ($pct !== null)
                                                <div class="absolute inset-y-0 left-0 rounded {{ $color }}" style="width: {{ min(100, $pct) }}%"></div>
                                            @endif
                                        </div>
                                        <span class="text-xs">{{ $pct !== null ? $pct.' %' : '—' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-white/20 font-semibold">
                            <td class="py-2" colspan="2">TOTAL</td>
                            <td class="text-right">{{ number_format($totals['budget'], 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($totals['committed'], 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($totals['spent'], 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($totals['available'], 0, ',', ' ') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Montants en GNF. Vert : sous le seuil · Orange : seuil atteint · Rouge : dépassement.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
