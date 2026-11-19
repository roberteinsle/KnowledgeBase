<?php

namespace App\Filament\Resources\Spaces;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Spaces\Pages\CreateSpace;
use App\Filament\Resources\Spaces\Pages\EditSpace;
use App\Filament\Resources\Spaces\Pages\ListSpaces;
use App\Models\User;
use App\Modules\Groups\Models\Group;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Services\SpaceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpaceResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Space::class;

    protected static ?string $slug = 'spaces';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Space');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Spaces');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Content');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Space'))->columns(2)->schema([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(100),
                TextInput::make('icon')->label(__('Icon (emoji)'))->maxLength(16)
                    ->helperText(__('Used when no image is uploaded.')),
                FileUpload::make('icon_path')->label(__('Icon (image)'))
                    ->image()
                    ->avatar()
                    ->imageEditor()
                    ->disk('local')
                    ->directory('space-icons')
                    ->visibility('private')
                    // Raster images only: SVG can contain scripts.
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                    ->maxSize(1024)
                    ->helperText(__('PNG, JPG, WebP or GIF, at most 1 MB. Shown square.')),
                TextInput::make('slug')->label(__('URL name'))->maxLength(100)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->notIn(Space::RESERVED_SLUGS)
                    ->unique(ignoreRecord: true)
                    ->helperText(__('Lower case letters, digits and hyphens. Left empty, it is derived from the name.')),
                Textarea::make('description')->label(__('Description'))->maxLength(2000)->rows(3)->columnSpanFull(),
            ]),
            Section::make(__('Permissions'))
                ->description(__('Who may read or edit this space. Administrators of the workspace always have full access.'))
                ->schema([
                    Repeater::make('permissions')->hiddenLabel()
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel(__('Add permission'))
                        ->schema([
                            Select::make('subject_type')->label(__('Type'))->required()->live()
                                ->options(['group' => __('Group'), 'user' => __('User')])
                                ->in(['group', 'user'])
                                ->default('group'),
                            Select::make('subject_id')->label(__('Group or user'))->required()->searchable()
                                ->options(fn (Get $get) => $get('subject_type') === 'user'
                                    ? User::query()->orderBy('name')->pluck('name', 'id')
                                    : Group::query()->orderBy('name')->pluck('name', 'id')),
                            Select::make('role')->label(__('Role'))->required()
                                ->options(SpaceRole::options())
                                ->in(array_keys(SpaceRole::options()))
                                ->default(SpaceRole::Reader->value),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // The app's own icon route (with permission check), not a storage URL.
                ImageColumn::make('icon_image')->label(__('Icon'))
                    ->state(fn (Space $record) => $record->iconUrl())
                    ->circular()
                    ->imageSize(32),
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),

                TextColumn::make('slug')->label(__('URL name')),
                TextColumn::make('permissions_count')->label(__('Permissions'))->counts('permissions'),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('open')->label(__('Open'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Space $record) => route('spaces.show', $record)),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('All pages, versions and attachments of this space are deleted permanently.'))
                    ->using(fn (Space $record) => app(SpaceService::class)->delete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpaces::route('/'),
            'create' => CreateSpace::route('/create'),
            'edit' => EditSpace::route('/{record}/edit'),
        ];
    }
}
