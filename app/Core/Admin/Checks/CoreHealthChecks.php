<?php

namespace App\Core\Admin\Checks;

use App\Core\Admin\HealthCheck;
use App\Core\Admin\HealthResult;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Small closure-based checks owned by Core (database, storage, configuration). */
final class CoreHealthChecks
{
    /** @return list<HealthCheck> */
    public static function all(): array
    {
        return [
            self::make('database', 'Database', 10, function () {
                $version = DB::selectOne('show server_version')->server_version ?? '?';

                return HealthResult::ok(__('Connected, PostgreSQL :version', ['version' => $version]));
            }),
            self::make('storage', 'File storage', 20, function () {
                $disk = Storage::disk('local');
                $probe = '.health-'.bin2hex(random_bytes(4));
                $disk->put($probe, 'ok');
                $disk->delete($probe);

                return HealthResult::ok(__('Writable'));
            }),
            self::make('app', 'Application', 30, function () {
                if (config('app.debug') && ! app()->isLocal()) {
                    return HealthResult::error(__('APP_DEBUG is on – turn it off in production.'));
                }
                if (! app()->isLocal() && ! str_starts_with((string) config('app.url'), 'https://')) {
                    return HealthResult::warning(__('APP_URL does not use https.'));
                }

                return HealthResult::ok(__('Version :version · PHP :php · Laravel :laravel', [
                    'version' => config('knowledgebase.version'),
                    'php' => PHP_VERSION,
                    'laravel' => app()->version(),
                ]));
            }),
        ];
    }

    /** @param Closure(): HealthResult $run */
    public static function make(string $key, string $label, int $order, Closure $run): HealthCheck
    {
        return new class($key, $label, $order, $run) implements HealthCheck
        {
            public function __construct(private string $key, private string $label, private int $order, private Closure $run) {}

            public function key(): string
            {
                return $this->key;
            }

            public function label(): string
            {
                return $this->label;
            }

            public function order(): int
            {
                return $this->order;
            }

            public function run(): HealthResult
            {
                return ($this->run)();
            }
        };
    }
}
