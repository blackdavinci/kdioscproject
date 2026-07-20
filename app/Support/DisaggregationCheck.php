<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Contrôle de cohérence d'une désagrégation de participants (RGA-05) :
 * pour un total donné, la somme par sexe et la somme par tranche d'âge
 * doivent chacune égaler le total.
 */
class DisaggregationCheck
{
    /**
     * @param  array<string, int|null>  $sex  décompte par sexe (femme, homme)
     * @param  array<string, int|null>  $age  décompte par tranche d'âge
     * @return list<string> messages d'incohérence (vide si cohérent)
     */
    public static function issues(int $total, array $sex, array $age): array
    {
        $issues = [];

        $sexSum = array_sum(array_map('intval', $sex));
        if ($sexSum !== $total) {
            $issues[] = "La somme par sexe ({$sexSum}) ne correspond pas au total ({$total}).";
        }

        $ageSum = array_sum(array_map('intval', $age));
        if ($ageSum !== $total) {
            $issues[] = "La somme par tranche d'âge ({$ageSum}) ne correspond pas au total ({$total}).";
        }

        return $issues;
    }

    /**
     * @param  array<string, int|null>  $sex
     * @param  array<string, int|null>  $age
     */
    public static function isCoherent(int $total, array $sex, array $age): bool
    {
        return self::issues($total, $sex, $age) === [];
    }
}
