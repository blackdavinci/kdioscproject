<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Erreurs métier du flux d'invitation (RG-07/16), avec messages FR destinés à
 * l'utilisateur (§7).
 */
class InvitationException extends RuntimeException
{
    public static function notAcceptable(): self
    {
        return new self(__('invitations.link_invalid'));
    }

    public static function teamMemberAlreadyLinked(): self
    {
        return new self(__('invitations.member_already_linked'));
    }

    public static function teamMemberFromAnotherOrganization(): self
    {
        return new self(__('invitations.member_other_organization'));
    }
}
