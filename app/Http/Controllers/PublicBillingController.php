<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Billing\InitiateDjomyPayment;
use App\Enums\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Page publique de règlement d'abonnement (RGF-15), accessible **sans session** : une
 * organisation suspendue pour impayé — donc bloquée à la connexion — peut y régler sa
 * facture en attente et déclencher la réactivation automatique.
 */
class PublicBillingController extends Controller
{
    public function show(): View
    {
        return view('billing.settle');
    }

    public function pay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = mb_strtolower(trim($validated['email']));

        // On identifie l'organisation via l'admin ; l'accès à la facture reste scopé à celle-ci.
        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        $invoice = $user instanceof User
            ? Invoice::query()
                ->where('organization_id', $user->organization_id)
                ->where('status', InvoiceStatus::Pending->value)
                ->orderByDesc('issued_at')
                ->first()
            : null;

        if ($invoice instanceof Invoice) {
            $result = (new InitiateDjomyPayment)->handle($invoice);

            if ($result['success'] === true && ! empty($result['payment_url'])) {
                return redirect()->away((string) $result['payment_url']);
            }
        }

        // Réponse générique (anti-énumération) : on ne révèle pas si l'adresse existe.
        return back()->with('status', __('billing.settle_generic'));
    }
}
