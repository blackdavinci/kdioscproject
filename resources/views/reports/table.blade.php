<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-size: 15px; font-weight: bold; color: #4f46e5; }
        h1 { font-size: 15px; margin: 6px 0 2px; }
        .muted { color: #6b7280; }
        .meta { margin: 4px 0 12px; }
        .meta span { margin-right: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 5px 6px; border: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 10px; }
        tr:last-child td { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">KIDIANI OSC — {{ $organization }}</div>
        <h1>{{ $title }}</h1>
        <div class="muted">{{ $subtitle }}</div>
        <div class="meta muted">
            @foreach ($meta as $label => $value)
                <span>{{ $label }} : {{ $value }}</span>
            @endforeach
            <span>Généré le {{ $generatedAt }}</span>
        </div>
    </div>

    @if (count($rows) === 0)
        <p class="muted">Aucune donnée pour ce rapport.</p>
    @else
        <table>
            <thead>
                <tr>@foreach ($headings as $h)<th>{{ $h }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
