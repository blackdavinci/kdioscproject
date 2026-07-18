@component('mail::message')
# Invitation à rejoindre {{ $organizationName }}

Vous avez été invité(e) à créer votre compte sur la plateforme **KIDIANI OSC** au
sein de l'organisation **{{ $organizationName }}**, avec le rôle
**{{ $roleLabel }}**.

Cliquez sur le bouton ci-dessous pour définir votre mot de passe et activer votre
compte. Ce lien est valable jusqu'au **{{ $expiresAt->translatedFormat('d/m/Y à H:i') }}**.

@component('mail::button', ['url' => $acceptUrl])
Activer mon compte
@endcomponent

Si vous n'attendiez pas cette invitation, vous pouvez ignorer cet e-mail.

Merci,<br>
L'équipe KIDIANI OSC
@endcomponent
