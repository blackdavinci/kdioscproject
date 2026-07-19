@php
    $tenant = \Filament\Facades\Filament::getTenant();
    $session = $tenant instanceof \App\Models\Organization
        ? \App\Models\AssistanceSession::activeFor($tenant->getKey())
        : null;
@endphp

@if ($session)
    <div style="background:#b45309;color:#fff;padding:.6rem 1rem;text-align:center;font-size:.875rem;font-weight:600;">
        Un accès d’assistance technique par {{ $session->operator?->name ?? 'l’équipe plateforme' }}
        est actif — expire dans {{ $session->remainingHours() }} h.
    </div>
@endif
