<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use App\Models\Contracts\Commentable;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Activité de terrain (RGA-01) : occurrence datée d'un nœud « activité » du cadre
 * logique. Porte la planification et la réalisation (saisie différée : realized_at).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $logframe_node_id
 * @property string $title
 * @property Carbon $planned_start
 * @property Carbon|null $planned_end
 * @property ActivityStatus $status
 * @property Carbon|null $realized_at
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $recurrence_group_id
 */
class Activity extends Model implements Commentable, HasMedia
{
    use BelongsToOrganization;

    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    use HasUlids;
    use InteractsWithMedia;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => ActivityStatus::Planifiee->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_start' => 'date',
            'planned_end' => 'date',
            'realized_at' => 'date',
            'status' => ActivityStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Justificatifs internes (RGA-08) : photos et listes de présence scannées.
     * Formats limités ; taille plafonnée à 10 Mo ; jamais exposés au bailleur.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('justificatifs')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->nonQueued()
            ->performOnCollections('justificatifs')
            ->width(1600)
            ->height(1600);
    }

    public function isRealized(): bool
    {
        return $this->status === ActivityStatus::Realisee;
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<LogframeNode, $this> */
    public function logframeNode(): BelongsTo
    {
        return $this->belongsTo(LogframeNode::class);
    }

    /** @return BelongsTo<GeoUnit, $this> */
    public function geoUnit(): BelongsTo
    {
        return $this->belongsTo(GeoUnit::class);
    }

    /** @return BelongsTo<Locality, $this> */
    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    /** @return BelongsTo<User, $this> */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /** @return BelongsTo<TeamMember, $this> */
    public function responsibleTeamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'responsible_team_member_id');
    }

    /** @return HasMany<ActivityDisaggregation, $this> */
    public function disaggregations(): HasMany
    {
        return $this->hasMany(ActivityDisaggregation::class);
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function responsibleName(): string
    {
        if ($this->responsibleUser instanceof User) {
            return $this->responsibleUser->getFilamentName();
        }

        return $this->responsibleTeamMember instanceof TeamMember ? $this->responsibleTeamMember->full_name : '—';
    }
}
