<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Invitations\AcceptInvitation;
use App\Models\Invitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Acceptation d'une invitation (RG-07/16/17). La route est signée (72 h) ; le jeton
 * en clair est comparé au haché stocké. Un lien invalide/expiré renvoie une page
 * dédiée invitant à en demander un nouveau.
 */
class AcceptInvitationController extends Controller
{
    public function show(Request $request, Invitation $invitation, string $token): View
    {
        if (! $this->tokenMatches($invitation, $token) || ! $invitation->isPending()) {
            return view('invitations.expired');
        }

        return view('invitations.accept', [
            'invitation' => $invitation,
            'token' => $token,
            'needsFullName' => $invitation->team_member_id === null,
        ]);
    }

    public function store(Request $request, Invitation $invitation, string $token): RedirectResponse
    {
        abort_unless($this->tokenMatches($invitation, $token) && $invitation->isPending(), 403);

        $validated = $request->validate([
            'full_name' => [$invitation->team_member_id === null ? 'required' : 'nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(10)->uncompromised()],
        ]);

        (new AcceptInvitation)->handle(
            $invitation,
            $validated['password'],
            $validated['full_name'] ?? null,
        );

        return redirect()
            ->to('/app/login')
            ->with('status', __('invitations.accepted'));
    }

    protected function tokenMatches(Invitation $invitation, string $token): bool
    {
        return hash_equals($invitation->token_hash, hash('sha256', $token));
    }
}
