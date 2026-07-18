<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type de bailleur (RG-20).
 */
enum DonorType: string
{
    case Multilateral = 'multilateral';
    case Bilateral = 'bilateral';
    case Foundation = 'foundation';
    case Private = 'private';
    case PublicNational = 'public_national';

    public function label(): string
    {
        return match ($this) {
            self::Multilateral => 'Multilatéral',
            self::Bilateral => 'Bilatéral',
            self::Foundation => 'Fondation',
            self::Private => 'Privé',
            self::PublicNational => 'Public national',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $type): array => $carry + [$type->value => $type->label()],
            [],
        );
    }
}
