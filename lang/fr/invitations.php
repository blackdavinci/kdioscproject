<?php

declare(strict_types=1);

/**
 * Messages du flux d'invitation (§7), en français.
 */
return [
    // Message générique renvoyé quelle que soit l'issue réelle (anti-énumération, RG-07).
    'generic_sent' => 'Si cette adresse est éligible, une invitation lui a été envoyée.',

    // Lien expiré / déjà utilisé / falsifié.
    'link_invalid' => 'Ce lien d’invitation n’est plus valable. Demandez un nouveau lien à votre administrateur.',
    'link_expired_title' => 'Lien d’invitation expiré',
    'link_expired_body' => 'Ce lien a expiré. Vous pouvez demander à votre administrateur de vous renvoyer une invitation.',

    // Liaison à une fiche membre existante (RG-16).
    'member_already_linked' => 'Cette fiche membre est déjà rattachée à un compte.',
    'member_other_organization' => 'Cette fiche membre appartient à une autre organisation.',

    // Confirmation d'activation.
    'accepted' => 'Votre compte est activé. Vous pouvez maintenant vous connecter.',
];
