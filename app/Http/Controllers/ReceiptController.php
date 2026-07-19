<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reçu PDF d'un paiement d'abonnement (RGF-17). Téléchargeable par l'admin de
 * l'organisation concernée (sa propre facture) et par le super-admin (toute facture).
 */
class ReceiptController extends Controller
{
    public function download(Invoice $invoice): Response
    {
        abort_unless($this->authorized($invoice), 403);
        abort_unless($invoice->status === InvoiceStatus::Paid, 404);

        $organization = $invoice->organization()->first();
        $payment = Payment::query()
            ->where('invoice_id', $invoice->getKey())
            ->where('status', 'succeeded')
            ->latest('paid_at')
            ->first();

        $pdf = Pdf::loadView('billing.receipt', [
            'invoice' => $invoice,
            'organizationName' => $organization instanceof Organization ? $organization->name : 'Organisation',
            'method' => $payment instanceof Payment ? $payment->method->label() : '—',
        ]);

        return $pdf->download('recu-'.$invoice->number.'.pdf');
    }

    protected function authorized(Invoice $invoice): bool
    {
        if (Auth::guard('platform')->user() instanceof PlatformUser) {
            return true;
        }

        $user = Auth::guard('web')->user();

        return $user instanceof User && $user->organization_id === $invoice->organization_id;
    }
}
