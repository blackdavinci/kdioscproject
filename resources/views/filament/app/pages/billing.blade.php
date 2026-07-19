<x-filament-panels::page>
    @php($sub = $this->subscription())

    <x-filament::section>
        <x-slot name="heading">Mon abonnement</x-slot>

        @if ($sub)
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Statut</div>
                    <x-filament::badge :color="$sub->status->getColor()">{{ $sub->status->getLabel() }}</x-filament::badge>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Plan</div>
                    <div class="font-semibold">{{ $sub->plan?->name }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Échéance</div>
                    <div class="font-semibold">{{ $sub->current_period_end?->format('d/m/Y') ?? ($sub->trial_ends_at?->format('d/m/Y') ?? '—') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Montant</div>
                    <div class="font-semibold">{{ number_format($sub->plan?->amount_gnf ?? 0, 0, ',', ' ') }} GNF</div>
                </div>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucun abonnement pour cette organisation.</p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Factures</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2">N°</th>
                        <th>Montant</th>
                        <th>Période</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->invoices() as $invoice)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2 font-medium">{{ $invoice->number }}</td>
                            <td>{{ number_format($invoice->amount_gnf, 0, ',', ' ') }} GNF</td>
                            <td>{{ $invoice->period_start?->format('d/m/Y') }} → {{ $invoice->period_end?->format('d/m/Y') }}</td>
                            <td>{{ $invoice->due_date?->format('d/m/Y') }}</td>
                            <td><x-filament::badge :color="$invoice->status->getColor()">{{ $invoice->status->getLabel() }}</x-filament::badge></td>
                            <td class="text-right">
                                @if ($invoice->status === \App\Enums\InvoiceStatus::Pending)
                                    <x-filament::button size="sm" wire:click="pay('{{ $invoice->id }}')" wire:loading.attr="disabled">
                                        Payer
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-gray-500 dark:text-gray-400">Aucune facture.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
