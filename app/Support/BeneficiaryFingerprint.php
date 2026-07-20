<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Empreinte non réversible d'un nom de bénéficiaire (RGSE-10), salée par
 * l'organisation, pour détecter les doublons probables sans stocker de nominatif
 * exploitable. Ne permet jamais de reconstituer le nom.
 */
class BeneficiaryFingerprint
{
    public static function make(string $organizationId, ?string $fullName): ?string
    {
        $normalized = self::normalize($fullName);

        if ($normalized === '') {
            return null;
        }

        return hash('sha256', $organizationId.'|'.$normalized);
    }

    private static function normalize(?string $value): string
    {
        // Retire les accents (translittération) pour un rapprochement souple.
        $value = Str::ascii(trim((string) $value));
        $value = mb_strtolower($value);

        // Réduit les espaces multiples.
        return (string) (preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
