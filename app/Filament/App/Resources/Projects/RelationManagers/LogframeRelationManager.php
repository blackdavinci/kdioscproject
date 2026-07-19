<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Projects\RelationManagers;

use App\Enums\LogframeNodeType;
use App\Models\LogframeNode;
use App\Models\Project;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LogframeRelationManager extends RelationManager
{
    protected static string $relationship = 'logframeNodes';

    protected static ?string $title = 'Cadre logique';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Type')
                ->options(LogframeNodeType::class)
                ->required()
                ->live(),
            Select::make('parent_id')
                ->label('Rattaché à')
                ->options(fn (): array => $this->parentOptions())
                ->searchable()
                ->helperText('Nœud de niveau supérieur (laisser vide pour la racine).'),
            TextInput::make('code')
                ->label('Code')
                ->maxLength(50)
                ->helperText('Proposé automatiquement, modifiable (OS1, R1.1, A1.1.1…).'),
            TextInput::make('title')
                ->label('Intitulé')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Description')
                ->rows(2)
                ->columnSpanFull(),
            TextInput::make('position')
                ->label('Ordre')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->placeholder('—'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('title')
                    ->label('Intitulé')
                    ->wrap(),
                TextColumn::make('parent.title')
                    ->label('Rattaché à')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un nœud'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected function parentOptions(): array
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof Project) {
            return [];
        }

        return $owner->logframeNodes()
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn (LogframeNode $n): array => [$n->id => trim(($n->code ? $n->code.' — ' : '').$n->title)])
            ->all();
    }
}
