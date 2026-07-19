<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\App\Resources\Projects\ProjectResource;
use App\Filament\App\Resources\Projects\Support\ProjectTransition;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transition')
                ->label('Changer le statut')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (Project $record): bool => $record->status->allowedTransitions() !== [])
                ->schema([
                    Select::make('to_status')
                        ->label('Nouveau statut')
                        ->options(fn (Project $record): array => collect($record->status->allowedTransitions())
                            ->mapWithKeys(fn (ProjectStatus $s): array => [$s->value => $s->label()])
                            ->all())
                        ->required()
                        ->live(),
                    Textarea::make('reason')
                        ->label('Motif')
                        ->rows(2)
                        ->required(fn (Get $get): bool => ($status = ProjectStatus::tryFrom((string) $get('to_status'))) !== null && $status->requiresReason())
                        ->helperText('Obligatoire pour une suspension ou une clôture.'),
                ])
                ->action(function (Project $record, array $data): void {
                    ProjectTransition::apply($record, ProjectStatus::from($data['to_status']), $data['reason'] ?? null);
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
