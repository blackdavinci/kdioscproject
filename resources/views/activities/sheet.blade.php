<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 18px; }
        .brand { font-size: 16px; font-weight: bold; color: #4f46e5; }
        h1 { font-size: 16px; margin: 6px 0 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { padding: 6px 8px; border: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; }
        .section { margin-top: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">KIDIANI OSC</div>
        <div class="muted">Fiche d’activité</div>
        <h1>{{ $activity->title }}</h1>
    </div>

    <table>
        <tr><td class="muted" style="width:35%">Projet</td><td>{{ $activity->project?->title }}</td></tr>
        <tr><td class="muted">Cadre logique</td><td>{{ trim(($activity->logframeNode?->code ? $activity->logframeNode->code.' — ' : '').($activity->logframeNode?->title ?? '')) ?: '—' }}</td></tr>
        <tr><td class="muted">Date prévue</td><td>{{ $activity->planned_start?->format('d/m/Y') }}@if($activity->planned_end) → {{ $activity->planned_end->format('d/m/Y') }}@endif</td></tr>
        <tr><td class="muted">Lieu</td><td>{{ $activity->geoUnit?->name ?? $activity->locality?->name ?? '—' }}</td></tr>
        <tr><td class="muted">Responsable</td><td>{{ $activity->responsibleName() }}</td></tr>
        <tr><td class="muted">Ressources prévues</td><td>{{ $activity->planned_resources ?: '—' }}</td></tr>
    </table>

    <div class="section">Participants prévus</div>
    <table>
        <tr>
            <th>Total</th>
            @foreach ($sexes as $sex)<th>{{ $sex->label() }}</th>@endforeach
            @foreach ($brackets as $bracket)<th>{{ $bracket->label() }}</th>@endforeach
        </tr>
        <tr>
            <td>{{ $planned['total'] }}</td>
            @foreach ($sexes as $sex)<td>{{ $planned['sex'][$sex->value] ?? 0 }}</td>@endforeach
            @foreach ($brackets as $bracket)<td>{{ $planned['age'][$bracket->value] ?? 0 }}</td>@endforeach
        </tr>
    </table>

    <p class="muted" style="margin-top:28px">Document généré pour le circuit terrain — à compléter et saisir en différé.</p>
</body>
</html>
