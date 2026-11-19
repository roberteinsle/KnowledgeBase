<?php

namespace App\Filament\Resources\Labels;

use App\Core\Activity\ActivityLogger;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Labels\Pages\ListLabels;
use App\Modules\Pages\Models\Label;
use App\Modules\Pages\Services\LabelNormalizer;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LabelResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Label::class;

    protected static ?string $slug = 'labels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return __('Label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Labels');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Content');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(50)
                ->dehydrateStateUsing(fn (string $state) => LabelNormalizer::normalize($state)[0] ?? $state)
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable()->badge(),
                TextColumn::make('pages_count')->label(__('Pages'))->counts('pages')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()->after(fn (Label $record) => app(ActivityLogger::class)->log('label.updated', $record, ['name' => $record->name])),
                DeleteAction::make()->before(fn (Label $record) => app(ActivityLogger::class)->log('label.deleted', $record, ['name' => $record->name])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLabels::route('/')];
    }
}
