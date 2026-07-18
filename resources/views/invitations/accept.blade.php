<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activer mon compte — KIDIANI OSC</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; }
        .card { max-width: 26rem; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: .75rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 1.25rem; margin-top: 0; }
        label { display: block; margin: 1rem 0 .25rem; font-weight: 600; font-size: .875rem; }
        input { width: 100%; padding: .6rem .75rem; border: 1px solid #cbd5e1; border-radius: .5rem; box-sizing: border-box; }
        button { margin-top: 1.5rem; width: 100%; padding: .7rem; background: #f59e0b; color: #fff; border: 0; border-radius: .5rem; font-weight: 600; cursor: pointer; }
        .muted { color: #64748b; font-size: .875rem; }
        .errors { background: #fef2f2; color: #b91c1c; padding: .75rem 1rem; border-radius: .5rem; font-size: .875rem; }
        .errors ul { margin: .25rem 0 0; padding-left: 1.25rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Activer mon compte</h1>
        <p class="muted">
            Organisation <strong>{{ $invitation->organization->name }}</strong> —
            rôle <strong>{{ $invitation->role->label() }}</strong>.
        </p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('invitation.accept.store', ['invitation' => $invitation, 'token' => $token]) }}">
            @csrf

            @if ($needsFullName)
                <label for="full_name">Nom complet</label>
                <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required autofocus>
            @endif

            <label for="password">Mot de passe (10 caractères minimum)</label>
            <input id="password" name="password" type="password" required>

            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required>

            <button type="submit">Activer mon compte</button>
        </form>
    </div>
</body>
</html>
