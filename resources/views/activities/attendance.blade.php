<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 12px; }
        .brand { font-size: 15px; font-weight: bold; color: #4f46e5; }
        .muted { color: #6b7280; }
        .meta td { padding: 3px 6px; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.grid td, table.grid th { border: 1px solid #9ca3af; padding: 6px 6px; }
        table.grid th { background: #f3f4f6; font-size: 10px; }
        .num { width: 6%; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">KIDIANI OSC</div>
        <div class="muted">Liste de présence</div>
    </div>

    <table class="meta">
        <tr><td class="muted">Activité</td><td><strong>{{ $activity->title }}</strong></td><td class="muted">Date</td><td>______/______/__________</td></tr>
        <tr><td class="muted">Projet</td><td>{{ $activity->project?->title }}</td><td class="muted">Lieu</td><td>{{ $activity->geoUnit?->name ?? $activity->locality?->name ?? '________________' }}</td></tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th class="num">N°</th>
                <th>Nom et prénom</th>
                <th>Sexe (F/H)</th>
                <th>Tranche d’âge</th>
                <th>Contact</th>
                <th>Signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i)
                <tr>
                    <td class="num">{{ $i }}</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted" style="margin-top:10px">Tranches d’âge : @foreach ($brackets as $b){{ $b->label() }}@if(! $loop->last) · @endif @endforeach</p>
</body>
</html>
