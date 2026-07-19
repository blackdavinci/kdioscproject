<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 12px; margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .title { font-size: 16px; font-weight: bold; margin: 24px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td, th { padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; }
        .total { font-size: 14px; font-weight: bold; }
        .muted { color: #6b7280; }
        .paid { display: inline-block; background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">KIDIANI OSC</div>
        <div class="muted">Reçu de paiement d’abonnement</div>
    </div>

    <table>
        <tr><td class="muted">Organisation</td><td>{{ $organizationName }}</td></tr>
        <tr><td class="muted">N° de facture</td><td>{{ $invoice->number }}</td></tr>
        <tr><td class="muted">Période couverte</td><td>{{ $invoice->period_start->format('d/m/Y') }} — {{ $invoice->period_end->format('d/m/Y') }}</td></tr>
        <tr><td class="muted">Date de paiement</td><td>{{ $invoice->paid_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="muted">Moyen de paiement</td><td>{{ $method }}</td></tr>
        <tr><td class="muted">Statut</td><td><span class="paid">Payée</span></td></tr>
    </table>

    <div class="title">Montant réglé</div>
    <table>
        <tr>
            <th>Désignation</th>
            <th style="text-align:right">Montant</th>
        </tr>
        <tr>
            <td>Abonnement à la plateforme KIDIANI OSC</td>
            <td style="text-align:right" class="total">{{ number_format($invoice->amount_gnf, 0, ',', ' ') }} GNF</td>
        </tr>
    </table>

    <p class="muted" style="margin-top:32px">
        Ce reçu atteste du règlement de la facture ci-dessus. Émis par KIDIANI SARL.
    </p>
</body>
</html>
