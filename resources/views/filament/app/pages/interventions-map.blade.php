<x-filament-panels::page>
    @php($points = $this->points())

    <x-filament::section>
        @if (count($points) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune activité géolocalisée pour le moment (renseignez la latitude et la longitude d’une activité).</p>
        @endif

        <div
            wire:ignore
            x-data
            x-init="
                const points = @js($points);
                const run = () => {
                    if (window.initKdiMap) { window.initKdiMap('interventions-map', points); }
                    else { setTimeout(run, 120); }
                };
                run();
            "
        >
            <div id="interventions-map" style="height: 32rem;" class="rounded-lg overflow-hidden border border-gray-200 dark:border-white/10"></div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
