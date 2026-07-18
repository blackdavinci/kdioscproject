<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Erreurs métier d'administration des comptes et organisations (RG-11), messages FR.
 */
class AdministrationException extends RuntimeException
{
    public static function lastActiveAdmin(): self
    {
        return new self(__('tenancy.last_admin_required'));
    }
}
