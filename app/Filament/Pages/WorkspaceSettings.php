<?php

namespace App\Filament\Pages;

use App\Core\Support\Locales;
use App\Filament\Concerns\AdminOnly;
use App\Modules\Workspace\Models\Workspace;
use App\Modules\Workspace\Services\WorkspaceService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class WorkspaceSettings extends Page implements HasSchemas
{
    use AdminOnly;
    use InteractsWithSchemas;

    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 80;

    protected string $view = 'filament.pages.workspace-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Workspace');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System');
    }

    public function getTitle(): string
    {
        return __('Workspace settings');
    }

    public function mount(): void
    {
        $this->form->fill(Workspace::current()->only(['name', 'description', 'default_locale', 'enforce_mfa']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(100),
                Textarea::make('description')->label(__('Description'))->maxLength(2000)->rows(3),
                Select::make('default_locale')->label(__('Default language'))->required()
                    ->options(Locales::all())->in(Locales::codes()),
                Toggle::make('enforce_mfa')->label(__('Require a passkey or authenticator app for everyone'))
                    ->helperText(__('Users who only sign in with e-mail links must set up a passkey or an authenticator app first.')),
            ])
            ->statePath('data');
    }

    public function save(WorkspaceService $workspace): void
    {
        $data = $this->form->getState();
        $workspace->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'default_locale' => $data['default_locale'],
            'enforce_mfa' => (bool) $data['enforce_mfa'],
        ]);

        Notification::make()->title(__('Saved.'))->success()->send();
    }
}
