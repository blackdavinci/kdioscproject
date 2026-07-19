<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Enums\SuspensionSource;
use App\Models\Billing\Subscription;
use App\Support\OrganizationNotificationSettings;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Organisation = tenant (RG-01/04/05). N'est pas elle-même scopée : c'est la racine
 * de tenance. Le logo est porté par la médiathèque (collection `logo`).
 *
 * @property string $id
 * @property string $name
 * @property OrganizationStatus $status
 * @property SuspensionSource|null $suspended_source
 * @property array<string, mixed>|null $contacts
 * @property array<string, mixed>|null $settings
 */
class Organization extends Model implements HasMedia
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUlids;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => OrganizationStatus::Active->value,
        'currency' => 'GNF',
        'fiscal_year_start' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contacts' => 'array',
            'settings' => 'array',
            'status' => OrganizationStatus::class,
            'suspended_source' => SuspensionSource::class,
            'fiscal_year_start' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === OrganizationStatus::Active;
    }

    /**
     * Paramétrage « notifications » de l'organisation (expéditeur affiché, reply-to,
     * SMS). Modèle centralisé : l'envoi effectif utilise le compte plateforme.
     */
    public function notificationSettings(): OrganizationNotificationSettings
    {
        return OrganizationNotificationSettings::fromOrganization($this);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<TeamMember, $this> */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /** @return HasMany<Donor, $this> */
    public function donors(): HasMany
    {
        return $this->hasMany(Donor::class);
    }

    /** @return HasMany<Locality, $this> */
    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }

    /** @return HasMany<Tag, $this> */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /** @return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /** @return HasOne<Subscription, $this> */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }
}
