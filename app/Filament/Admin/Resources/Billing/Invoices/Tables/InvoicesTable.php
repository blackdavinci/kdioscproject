<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Billing\Invoices\Tables;

use App\Actions\Billing\RecordManualPayment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Billing\Invoice;
use App\Models\PlatformUser;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('N° facture')
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organisation')
                    ->searchable(),
                TextColumn::make('amount_gnf')
                    ->label('Montant')
                    ->numeric(thousandsSeparator: ' ')
                    ->suffix(' GNF')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Payée le')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->defaultSort('issued_at', 'desc')
            ->recordActions([
                Action::make('recordPayment')
                    ->label('Enregistrer un paiement')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Pending)
                    ->schema([
                        Select::make('method')
                            ->label('Moyen de paiement')
                            ->options(PaymentMethod::manualOptions())
                            ->required(),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        $method = $data['method'] instanceof PaymentMethod
                            ? $data['method']
                            : PaymentMethod::from((string) $data['method']);

                        $recordedBy = Filament::auth()->user();

                        (new RecordManualPayment)->handle(
                            $record,
                            $method,
                            $recordedBy instanceof PlatformUser ? $recordedBy : null,
                        );

                        Notification::make()->success()->title('Paiement enregistré, facture soldée')->send();
                    }),
            ]);
    }
}
