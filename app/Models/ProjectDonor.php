<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Financement d'un projet par un bailleur (RGP-03). Montant en GNF (devise de
 * travail) ; montant en devise d'origine facultatif et informatif (pas de conversion).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $donor_id
 * @property int $amount_gnf
 * @property float|null $amount_foreign
 * @property string|null $foreign_currency
 */
class ProjectDonor extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_gnf' => 'integer',
            'amount_foreign' => 'decimal:2',
        ];
    }

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
}
