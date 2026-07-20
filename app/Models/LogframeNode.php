<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LogframeNodeType;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\LogframeNodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Nœud du cadre logique (RGP-08). Arbre par parent_id, rattaché au projet.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string|null $parent_id
 * @property LogframeNodeType $type
 * @property string|null $code
 * @property string $title
 * @property string|null $description
 * @property int $position
 */
class LogframeNode extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<LogframeNodeFactory> */
    use HasFactory;

    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LogframeNodeType::class,
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<LogframeNode, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(LogframeNode::class, 'parent_id');
    }

    /** @return HasMany<LogframeNode, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(LogframeNode::class, 'parent_id');
    }
}
