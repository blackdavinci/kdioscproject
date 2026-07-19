<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
        @foreach ([
            ['Régions (ADM1)', $regions],
            ['Préfectures (ADM2)', $prefectures],
            ['Sous-préfectures / communes (ADM3)', $communes],
            ['Total unités nationales', $total],
            ['Localités des organisations', $localities],
        ] as [$label, $value])
            <x-filament::section>
                <div class="text-3xl font-bold tabular-nums">{{ number_format($value, 0, ',', ' ') }}</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">Source</x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Découpage administratif national COD-AB (OCHA/HDX), 4 niveaux à P-codes stables.
            L’import est idempotent (RG-22) : relancez-le pour appliquer une nouvelle édition
            (ajouts, renommages) sans perte ni doublon.
        </p>
    </x-filament::section>
</x-filament-panels::page>
