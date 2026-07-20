<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cadre de résultats (RGSE-08) : sous-ensemble d'indicateurs d'un projet, en
 * général rattaché à un bailleur. Un indicateur peut figurer dans plusieurs cadres.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string|null $donor_id
 * @property string $name
 */
class ResultFramework extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Donor, $this> */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    /** @return BelongsToMany<Indicator, $this> */
    public function indicators(): BelongsToMany
    {
        return $this->belongsToMany(Indicator::class, 'result_framework_indicator');
    }
}
