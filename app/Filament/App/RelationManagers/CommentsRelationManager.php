<?php

declare(strict_types=1);

namespace App\Filament\App\RelationManagers;

use App\Actions\Comments\PostComment;
use App\Models\Comment;
use App\Models\Contracts\Commentable;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Fil de commentaires (RGT-08) avec mentions @ (RGT-09) — réutilisable sur une
 * tâche comme sur une activité. L'autocomplétion des mentions est limitée aux
 * comptes de l'organisation (global scope sur User).
 */
class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Commentaires';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->label('Commentaire')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Select::make('mentions')
                ->label('Mentionner')
                ->helperText('Notifie les personnes choisies (comptes de votre organisation).')
                ->multiple()
                ->searchable()
                ->dehydrated(false)
                ->options(fn (): array => self::mentionOptions()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('author.email')
                    ->label('Auteur'),
                TextColumn::make('body')
                    ->label('Commentaire')
                    ->wrap()
                    ->limit(140),
                TextColumn::make('edited_at')
                    ->label('Modifié')
                    ->formatStateUsing(fn ($state): string => $state ? 'Oui' : '—')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Publié le')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Commenter')
                    ->using(function (array $data, CommentsRelationManager $livewire): Comment {
                        $author = Filament::auth()->user();
                        $owner = $livewire->getOwnerRecord();
                        abort_unless($author instanceof User && $owner instanceof Commentable, 403);

                        return (new PostComment)->handle(
                            $owner,
                            $author,
                            (string) $data['body'],
                            array_map('strval', $data['mentions'] ?? []),
                        );
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Comment $record): bool => self::canManage($record))
                    ->mutateDataUsing(function (array $data): array {
                        $data['edited_at'] = now();

                        return $data;
                    }),
                DeleteAction::make()
                    ->visible(fn (Comment $record): bool => self::canManage($record)),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function mentionOptions(): array
    {
        $currentId = Filament::auth()->id();

        return User::query()
            ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
            ->get()
            ->mapWithKeys(fn (User $u): array => [$u->id => $u->getFilamentName()])
            ->all();
    }

    /** L'auteur ou un admin peut éditer/supprimer (jamais silencieux, RGT-08). */
    protected static function canManage(Comment $record): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ($record->user_id === $user->id || $user->hasRole('admin'));
    }
}
