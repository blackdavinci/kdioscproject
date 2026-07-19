<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Actions\Billing\InitiateDjomyPayment;
use App\Enums\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Models\Organization;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Écran « Abonnement & Facturation » de l'organisation (RGF-01, écran §5-5). Réservé à
 * l'admin de l'OSC : statut de l'abonnement, factures et paiement en ligne (Djomy).
 */
class Billing extends Page
{
    protected string $view = 'filament.app.pages.billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Abonnement';

    protected static ?string $title = 'Abonnement & Facturation';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation';

    protected static ?int $navigationSort = 9;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('admin');
    }

    public function subscription(): ?Subscription
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization) {
            return null;
        }

        return Subscription::query()->where('organization_id', $tenant->getKey())->first();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function invoices(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization) {
            return collect();
        }

        return Invoice::query()
            ->where('organization_id', $tenant->getKey())
            ->orderByDesc('issued_at')
            ->get();
    }

    /**
     * Initie un paiement Djomy pour une facture en attente et redirige vers Djomy.
     */
    public function pay(string $invoiceId): mixed
    {
        $tenant = Filament::getTenant();

        $invoice = Invoice::query()
            ->where('organization_id', $tenant instanceof Organization ? $tenant->getKey() : null)
            ->where('status', InvoiceStatus::Pending->value)
            ->whereKey($invoiceId)
            ->first();

        if (! $invoice instanceof Invoice) {
            Notification::make()->danger()->title('Facture introuvable')->send();

            return null;
        }

        $result = (new InitiateDjomyPayment)->handle($invoice);

        if ($result['success'] === true && ! empty($result['payment_url'])) {
            return redirect()->away((string) $result['payment_url']);
        }

        Notification::make()->danger()->title('Paiement indisponible')->body($result['error'] ?? '')->send();

        return null;
    }
}
