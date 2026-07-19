<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Classe de base des entités métier appartenant à une organisation (tenant).
 *
 * Impose les trois invariants transverses du socle :
 * - clé primaire ULID exposable sans fuite d'énumération (RG-03) ;
 * - isolation par organisation via le global scope (RG-01, RG-02) ;
 * - soft deletes (RG-25).
 *
 * Le référentiel géographique national (geo_units) et les entités du super-admin
 * n'héritent volontairement pas de cette classe : ils sont hors tenant.
 */
abstract class TenantModel extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;
}
