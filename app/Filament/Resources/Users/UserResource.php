<?php

namespace App\Filament\Resources\Users;

use App\Core\Support\Dates;
use App\Core\Support\Locales;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Modules\Auth\Services\MagicLinkService;
use App\Modules\Groups\Models\Group;
use App\Modules\Workspace\Services\UserService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('People & access');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
            TextInput::make('email')->label(__('E-mail'))->required()->email()->maxLength(255)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (string $state) => MagicLinkService::normalizeEmail($state)),
            Select::make('role')->label(__('Role'))->required()->native(false)
                ->options([User::ROLE_MEMBER => __('Member'), User::ROLE_ADMIN => __('Administrator')])
                ->default(User::ROLE_MEMBER)
                ->in([User::ROLE_MEMBER, User::ROLE_ADMIN]),
            Select::make('locale')->label(__('Language'))->options(Locales::all())->in(Locales::codes())
                ->placeholder(__('Workspace default')),
            Toggle::make('is_active')->label(__('Active'))->default(true),
            Select::make('groups')->label(__('Groups'))->multiple()
                ->options(fn () => Group::query()->whereNull('system_key')->orderBy('name')->pluck('name', 'id'))
                ->helperText(__('Everyone belongs to the group "Everyone" automatically.')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('E-mail'))->searchable()->sortable(),
                TextColumn::make('role')->label(__('Role'))->badge()
                    ->formatStateUsing(fn (string $state) => $state === User::ROLE_ADMIN ? __('Administrator') : __('Member')),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
                TextColumn::make('groups.name')->label(__('Groups'))->badge()->toggleable(),
                TextColumn::make('last_login_at')->label(__('Last sign-in'))->sortable()
                    ->formatStateUsing(fn ($state) => Dates::display($state)),
            ])
            ->filters([
                SelectFilter::make('role')->label(__('Role'))
                    ->options([User::ROLE_MEMBER => __('Member'), User::ROLE_ADMIN => __('Administrator')]),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('sendLink')
                    ->label(__('Send sign-in link'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->is_active)
                    ->action(function (User $record) {
                        app(MagicLinkService::class)->issue($record);
                        Notification::make()->title(__('Sign-in link sent.'))->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()->using(fn (User $record) => app(UserService::class)->delete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
