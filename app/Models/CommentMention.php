<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trace d'une personne mentionnée (@) dans un commentaire, et donc notifiée
 * (RGT-09). Toujours un compte de la même organisation.
 *
 * @property string $id
 * @property string $comment_id
 * @property string $user_id
 */
class CommentMention extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    /** @return BelongsTo<Comment, $this> */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
