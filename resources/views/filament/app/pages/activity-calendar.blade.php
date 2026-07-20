<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <x-filament::button color="gray" size="sm" wire:click="previousMonth" icon="heroicon-o-chevron-left">Préc.</x-filament::button>
        <div class="min-w-40 text-center font-semibold capitalize">{{ $this->monthLabel() }}</div>
        <x-filament::button color="gray" size="sm" wire:click="nextMonth" icon="heroicon-o-chevron-right" icon-position="after">Suiv.</x-filament::button>

        <div class="ml-auto max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="projectId">
                    <option value="">Tous les projets</option>
                    @foreach ($this->projectOptions() as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </div>

    <x-filament::section>
        <div class="grid grid-cols-7 gap-px overflow-hidden rounded-lg bg-gray-200 dark:bg-white/10 text-sm">
            @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dow)
                <div class="bg-gray-50 dark:bg-white/5 p-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ $dow }}</div>
            @endforeach

            @foreach ($this->weeks() as $week)
                @foreach ($week as $day)
                    <div class="min-h-24 bg-white dark:bg-gray-900 p-1.5 {{ $day['inMonth'] ? '' : 'opacity-40' }}">
                        <div class="mb-1 text-xs text-gray-400">{{ $day['date']->format('j') }}</div>
                        @foreach ($day['activities'] as $activity)
                            <div class="mb-1 truncate rounded px-1.5 py-0.5 text-xs text-white
                                {{ $activity->status->value === 'realisee' ? 'bg-success-500' : ($activity->status->value === 'annulee' ? 'bg-danger-400' : 'bg-primary-500') }}"
                                 title="{{ $activity->title }}">
                                {{ $activity->title }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
