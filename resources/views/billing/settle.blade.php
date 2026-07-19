<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Régler mon abonnement — KDI OSC</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; }
        .card { max-width: 26rem; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: .75rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 1.25rem; margin-top: 0; }
        p { color: #475569; font-size: .9rem; }
        label { display: block; margin: 1rem 0 .25rem; font-weight: 600; font-size: .875rem; }
        input { width: 100%; padding: .6rem .75rem; border: 1px solid #cbd5e1; border-radius: .5rem; box-sizing: border-box; }
        button { margin-top: 1.5rem; width: 100%; padding: .7rem; background: #4f46e5; color: #fff; border: 0; border-radius: .5rem; font-weight: 600; cursor: pointer; }
        .status { background: #eef2ff; color: #3730a3; padding: .75rem 1rem; border-radius: .5rem; font-size: .875rem; }
        .errors { background: #fef2f2; color: #b91c1c; padding: .5rem .75rem; border-radius: .5rem; font-size: .85rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Régler mon abonnement</h1>
        <p>Votre organisation est suspendue pour un abonnement échu ? Réglez votre facture ici pour réactiver l’accès.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('billing.settle.pay') }}">
            @csrf
            <label for="email">E-mail de l’administrateur de l’organisation</label>
            <input id="email" name="email" type="email" required autofocus>
            <button type="submit">Continuer vers le paiement</button>
        </form>
    </div>
</body>
</html>
