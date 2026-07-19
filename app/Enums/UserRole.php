<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Les 7 rôles fixes d'une organisation (RG-12/13). Un utilisateur a exactement
 * un rôle. Les rôles temporaires (consultant, bailleur) exigent une expiration (RG-10).
 */
enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case ChefProjet = 'chef_projet';
    case ResponsableSe = 'responsable_se';
    case ResponsableFinancier = 'responsable_financier';
    case AgentTerrain = 'agent_terrain';
    case Consultant = 'consultant';
    case Bailleur = 'bailleur';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::ChefProjet => 'Chef de projet',
            self::ResponsableSe => 'Responsable S&E',
            self::ResponsableFinancier => 'Responsable financier',
            self::AgentTerrain => 'Agent de terrain',
            self::Consultant => 'Consultant',
            self::Bailleur => 'Bailleur',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Rôles à durée déterminée : expiration obligatoire (RG-10).
     */
    public function isTemporary(): bool
    {
        return in_array($this, [self::Consultant, self::Bailleur], true);
    }

    /**
     * @return array<string, string> value => label, pour les sélecteurs Filament.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $role): array => $carry + [$role->value => $role->label()],
            [],
        );
    }
}
