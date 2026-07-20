<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskRecurrence;
use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Tâche (RGT-01) : rattachée à un projet et/ou une activité, ou interne (hors
 * projet). Assignée à un compte ou une fiche membre. Isolée par organisation.
 *
 * @property string $id
 * @property string $organization_id
 * @property string|null $project_id
 * @property string|null $activity_id
 * @property string $title
 * @property string|null $description
 * @property string|null $assignee_user_id
 * @property string|null $assignee_team_member_id
 * @property Carbon|null $due_date
 * @property TaskPriority $priority
 * @property TaskStatus $status
 * @property int $position
 * @property Carbon|null $completed_at
 * @property TaskRecurrence $recurrence
 * @property int|null $reminder_days_before
 * @property string|null $recurrence_group_id
 */
class Task extends Model implements HasMedia
{
    use BelongsToOrganization;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasUlids;
    use InteractsWithMedia;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => TaskStatus::AFaire->value,
        'priority' => TaskPriority::Normale->value,
        'recurrence' => TaskRecurrence::Aucune->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'recurrence' => TaskRecurrence::class,
            'position' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pieces_jointes')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    public function isInternal(): bool
    {
        return $this->project_id === null && $this->activity_id === null;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->status !== TaskStatus::Termine
            && $this->due_date->isPast();
    }

    public function assigneeName(): string
    {
        if ($this->assigneeUser instanceof User) {
            return $this->assigneeUser->getFilamentName();
        }

        return $this->assigneeTeamMember instanceof TeamMember ? $this->assigneeTeamMember->full_name : '—';
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assigneeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    /** @return BelongsTo<TeamMember, $this> */
    public function assigneeTeamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'assignee_team_member_id');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
