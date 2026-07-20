<x-filament-panels::page>
    <div class="mb-4 max-w-md">
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="projectId">
                @foreach ($this->projectOptions() as $id => $title)
                    <option value="{{ $id }}">{{ $title }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    @php($data = $this->gantt())

    @if (! $data)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucun projet à afficher.</p>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="mb-3 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ $data['start']->format('d/m/Y') }}</span>
                <span>{{ $data['end']->format('d/m/Y') }}</span>
            </div>

            @forelse ($data['bars'] as $bar)
                <div class="mb-2">
                    <div class="mb-1 text-sm">{{ $bar['title'] }} <span class="text-xs text-gray-400">({{ $bar['start'] }})</span></div>
                    <div class="relative h-6 rounded bg-gray-100 dark:bg-white/5">
                        <div class="absolute top-0 h-6 rounded {{ $bar['status']->value === 'realisee' ? 'bg-success-500' : ($bar['status']->value === 'annulee' ? 'bg-danger-400' : 'bg-primary-500') }}"
                             style="left: {{ $bar['offset'] }}%; width: {{ $bar['width'] }}%;"
                             title="{{ $bar['title'] }}"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Aucune activité planifiée pour ce projet.</p>
            @endforelse
        </x-filament::section>
    @endif
</x-filament-panels::page>
