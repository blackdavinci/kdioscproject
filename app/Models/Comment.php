<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Commentaire polymorphe (RGT-08) attaché à une tâche ou une activité.
 * Édition tracée (`edited_at`), suppression = soft delete jamais silencieux.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $commentable_type
 * @property string $commentable_id
 * @property string $user_id
 * @property string $body
 * @property Carbon|null $edited_at
 */
class Comment extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<CommentFactory> */
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
            'edited_at' => 'datetime',
        ];
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    /** @return MorphTo<Model, $this> */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<CommentMention, $this> */
    public function mentions(): HasMany
    {
        return $this->hasMany(CommentMention::class);
    }
}
