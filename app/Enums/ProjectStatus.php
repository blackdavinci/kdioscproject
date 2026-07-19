<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Cycle de vie d'un projet (RGP-05) : brouillon → validé → en cours → clôturé,
 * avec suspendu réversible depuis validé/en cours. `clôturé` est terminal.
 */
enum ProjectStatus: string implements HasColor, HasLabel
{
    case Brouillon = 'brouillon';
    case Valide = 'valide';
    case EnCours = 'en_cours';
    case Suspendu = 'suspendu';
    case Cloture = 'cloture';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Valide => 'Validé',
            self::EnCours => 'En cours',
            self::Suspendu => 'Suspendu',
            self::Cloture => 'Clôturé',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::Valide => 'info',
            self::EnCours => 'success',
            self::Suspendu => 'warning',
            self::Cloture => 'danger',
        };
    }

    /**
     * Transitions autorisées depuis cet état (RGP-05).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Brouillon => [self::Valide],
            self::Valide => [self::EnCours, self::Suspendu],
            self::EnCours => [self::Cloture, self::Suspendu],
            self::Suspendu => [self::Valide, self::EnCours],
            self::Cloture => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Suspension et clôture exigent un motif (RGP-06). */
    public function requiresReason(): bool
    {
        return in_array($this, [self::Suspendu, self::Cloture], true);
    }

    /** Le projet et son cadre logique sont en lecture seule (RGP-07). */
    public function isReadOnly(): bool
    {
        return in_array($this, [self::Suspendu, self::Cloture], true);
    }
}
