<?php

namespace App\Modules\Auth\Services;

use App\Core\Admin\HealthResult;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Throwable;

/**
 * Checks the mail setup on demand (sign-in links depend on it). The last
 * result is cached for the dashboard status.
 */
final class MailCheckService
{
    private const CACHE_KEY = 'admin.mail_check';

    /** Configuration only – cheap, runs on every dashboard view. */
    public function status(): HealthResult
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return HealthResult::warning(__('Mailer ":mailer": e-mails are not sent, only written to the log.', ['mailer' => $mailer]));
        }

        if ($mailer === 'smtp' && blank(config('mail.mailers.smtp.host'))) {
            return HealthResult::error(__('MAIL_HOST is not set – sign-in links cannot be sent.'));
        }

        $last = Cache::get(self::CACHE_KEY);
        $target = $mailer === 'smtp'
            ? config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port')
            : $mailer;

        if (! is_array($last)) {
            return HealthResult::warning(__(':target – not tested yet.', ['target' => $target]));
        }

        $message = __(':target – last check :date: :result', [
            'target' => $target,
            'date' => $last['at'],
            'result' => $last['message'],
        ]);

        return $last['ok'] ? HealthResult::ok($message) : HealthResult::error($message);
    }

    /** Connects and authenticates without sending anything. */
    public function testConnection(): HealthResult
    {
        try {
            $transport = Mail::mailer()->getSymfonyTransport();

            if (! $transport instanceof SmtpTransport) {
                return $this->remember(true, __('Mailer ":mailer" needs no connection.', ['mailer' => config('mail.default')]));
            }

            $stream = $transport->getStream();
            if ($stream instanceof SocketStream) {
                $stream->setTimeout(10);
            }
            $transport->start();
            $transport->stop();

            return $this->remember(true, __('Connection and sign-in successful.'));
        } catch (Throwable $e) {
            return $this->remember(false, __('Connection failed: :error', ['error' => $this->shorten($e->getMessage())]));
        }
    }

    public function sendTest(User $user): HealthResult
    {
        try {
            Mail::raw(__('This is a test e-mail from the administration of your knowledge base. Sending works.'), function ($message) use ($user) {
                $message->to($user->email)->subject(__('Test e-mail from the knowledge base'));
            });

            return $this->remember(true, __('Test e-mail sent to :email.', ['email' => $user->email]));
        } catch (Throwable $e) {
            return $this->remember(false, __('Sending failed: :error', ['error' => $this->shorten($e->getMessage())]));
        }
    }

    private function remember(bool $ok, string $message): HealthResult
    {
        Cache::put(self::CACHE_KEY, ['ok' => $ok, 'message' => $message, 'at' => now()->setTimezone(config('knowledgebase.display_timezone'))->format('d.m.Y H:i')], now()->addDays(30));

        return $ok ? HealthResult::ok($message) : HealthResult::error($message);
    }

    /** No credentials or long traces on the dashboard. */
    private function shorten(string $message): string
    {
        $message = preg_replace('/(password|passwd|pass)[^\s]*\s*\S+/i', '$1 ***', $message) ?? $message;

        return mb_substr(trim(preg_replace('/\s+/', ' ', $message) ?? $message), 0, 200);
    }
}
