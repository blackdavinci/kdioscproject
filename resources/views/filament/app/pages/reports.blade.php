<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-4">
            <div class="max-w-2xl">
                <label class="mb-1 block text-sm font-medium">Type de rapport</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="reportType">
                        @foreach ($this->reportTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            @if ($reportType === 'indicators')
                <div class="max-w-2xl">
                    <label class="mb-1 block text-sm font-medium">Cadre de résultats</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="frameworkId">
                            @foreach ($this->frameworkOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @else
                <div class="max-w-2xl">
                    <label class="mb-1 block text-sm font-medium">Projet</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="projectId">
                            @foreach ($this->projectOptions() as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif

            @if ($reportType === 'activities')
                <div class="flex max-w-2xl gap-4">
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium">Début de période</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="periodStart" />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium">Fin de période</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="periodEnd" />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <x-filament::button icon="heroicon-o-table-cells" wire:click="excel">Exporter en Excel</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-document-text" wire:click="pdf">Exporter en PDF</x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
