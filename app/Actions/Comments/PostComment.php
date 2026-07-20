<?php

declare(strict_types=1);

namespace App\Actions\Comments;

use App\Filament\App\Resources\Activities\ActivityResource;
use App\Filament\App\Resources\Tasks\TaskResource;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Contracts\Commentable;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CommentMentionMail;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Publie un commentaire (RGT-08) et notifie les personnes mentionnées (RGT-09/10) :
 * in-app (cloche Filament) + e-mail. Les mentions sont strictement limitées aux
 * comptes de l'organisation courante (le contrôle d'unicité tenant est assuré par
 * le global scope sur User) ; toute mention hors organisation est ignorée.
 */
class PostComment
{
    /**
     * @param  array<int, string>  $mentionUserIds
     */
    public function handle(Commentable $commentable, User $author, string $body, array $mentionUserIds = []): Comment
    {
        return DB::transaction(function () use ($commentable, $author, $body, $mentionUserIds): Comment {
            /** @var Comment $comment */
            $comment = $commentable->comments()->create([
                'user_id' => $author->id,
                'body' => $body,
            ]);

            // On ne retient que des comptes visibles dans le tenant courant (isolation).
            $mentioned = User::query()
                ->whereIn('id', $mentionUserIds)
                ->where('id', '!=', $author->id)
                ->get();

            foreach ($mentioned as $user) {
                $comment->mentions()->create(['user_id' => $user->id]);
            }

            $this->notify($comment, $commentable, $mentioned);

            return $comment;
        });
    }

    /**
     * @param  Collection<int, User>  $mentioned
     */
    private function notify(Comment $comment, Commentable $commentable, Collection $mentioned): void
    {
        if ($mentioned->isEmpty()) {
            return;
        }

        $label = $this->label($commentable);
        $url = $this->url($commentable);

        foreach ($mentioned as $user) {
            FilamentNotification::make()
                ->title('Vous avez été mentionné·e sur '.$label)
                ->body((string) str($comment->body)->limit(120))
                ->sendToDatabase($user);

            if (filled($user->email)) {
                $user->notify(new CommentMentionMail($comment, $label, $url ?? url('/')));
            }
        }
    }

    private function label(Commentable $commentable): string
    {
        return match (true) {
            $commentable instanceof Task => 'la tâche « '.$commentable->title.' »',
            $commentable instanceof Activity => 'l’activité « '.$commentable->title.' »',
            default => 'un élément',
        };
    }

    private function url(Commentable $commentable): ?string
    {
        try {
            if ($commentable instanceof Task) {
                return TaskResource::getUrl('edit', ['record' => $commentable]);
            }
            if ($commentable instanceof Activity) {
                return ActivityResource::getUrl('edit', ['record' => $commentable]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
