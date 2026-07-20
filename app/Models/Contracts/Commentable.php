<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Entité pouvant porter un fil de commentaires (RGT-08) : tâche ou activité.
 */
interface Commentable
{
    /** @return MorphMany<Comment, covariant \Illuminate\Database\Eloquent\Model> */
    public function comments(): MorphMany;
}
