<?php

namespace App\Filament\Resources\Groups;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Models\User;
use App\Modules\Groups\Models\Group;
use App\Modules\Groups\Services\GroupService;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Group::class;

    protected static ?string $slug = 'gruppen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Groups');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('People & access');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(100)
                ->unique(ignoreRecord: true)
                ->disabled(fn (?Group $record) => $record?->isEveryone() ?? false),
            Textarea::make('description')->label(__('Description'))->maxLength(1000)->rows(3),
            Select::make('users')->label(__('Members'))->multiple()->searchable()
                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                ->hidden(fn (?Group $record) => $record?->isEveryone() ?? false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable()
                    ->description(fn (Group $record) => $record->isEveryone() ? __('All users, automatically') : null),
                TextColumn::make('users_count')->label(__('Members'))->counts('users')
                    ->formatStateUsing(fn ($state, Group $record) => $record->isEveryone() ? __('all') : $state),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Group $record) => $record->isEveryone())
                    ->using(fn (Group $record) => app(GroupService::class)->delete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/neu'),
            'edit' => EditGroup::route('/{record}/bearbeiten'),
        ];
    }
}
