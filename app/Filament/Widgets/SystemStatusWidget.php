<?php

namespace App\Filament\Widgets;

use App\Core\Admin\HealthCheck;
use App\Core\Admin\HealthCheckRegistry;
use App\Core\Admin\HealthResult;
use App\Filament\Concerns\AdminOnly;
use App\Modules\Auth\Services\MailCheckService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

/** Status checks registered by the modules (HealthCheckRegistry) plus mail tests. */
class SystemStatusWidget extends Widget implements HasActions, HasSchemas
{
    use AdminOnly;
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.system-status';

    /** @return list<array{check: HealthCheck, result: HealthResult}> */
    public function getChecks(): array
    {
        return app(HealthCheckRegistry::class)->run();
    }

    public function testSmtpAction(): Action
    {
        return Action::make('testSmtp')
            ->label(__('Test SMTP connection'))
            ->icon(Heroicon::OutlinedSignal)
            ->color('gray')
            ->action(fn () => $this->notify(app(MailCheckService::class)->testConnection()));
    }

    public function sendTestMailAction(): Action
    {
        return Action::make('sendTestMail')
            ->label(__('Send test e-mail'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(fn () => __('A test e-mail is sent to :email.', ['email' => auth()->user()->email]))
            ->action(fn () => $this->notify(app(MailCheckService::class)->sendTest(auth()->user())));
    }

    private function notify(HealthResult $result): void
    {
        $notification = Notification::make()->title($result->message);
        $result->status === HealthResult::OK ? $notification->success() : $notification->danger();
        $notification->send();
    }

    public static function canView(): bool
    {
        return static::canAccess();
    }
}
