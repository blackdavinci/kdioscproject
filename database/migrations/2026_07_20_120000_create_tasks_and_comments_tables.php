<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collaboration (Spec 04) : tâches (projet/activité/interne), étiquettes,
 * commentaires polymorphes (tâche ou activité) et mentions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUlid('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assignee_team_member_id')->nullable()->constrained('team_members')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('normale');
            $table->string('status')->default('a_faire');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->string('recurrence')->default('aucune');
            $table->unsignedSmallInteger('reminder_days_before')->nullable();
            $table->ulid('recurrence_group_id')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index('due_date');
            $table->index('recurrence_group_id');
        });

        Schema::create('task_tag', function (Blueprint $table): void {
            $table->foreignUlid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUlid('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['task_id', 'tag_id']);
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->ulidMorphs('commentable'); // task | activity
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('comment_mentions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_mentions');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('task_tag');
        Schema::dropIfExists('tasks');
    }
};
