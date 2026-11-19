<?php

namespace App\Core\Admin;

use Throwable;

/** System status on the admin dashboard; every module registers its checks. */
final class HealthCheckRegistry
{
    /** @var array<string, HealthCheck> */
    private array $checks = [];

    public function add(HealthCheck $check): void
    {
        $this->checks[$check->key()] = $check;
    }

    /** @return list<array{check: HealthCheck, result: HealthResult}> */
    public function run(): array
    {
        $checks = array_values($this->checks);
        usort($checks, fn (HealthCheck $a, HealthCheck $b) => $a->order() <=> $b->order());

        return array_map(function (HealthCheck $check) {
            try {
                $result = $check->run();
            } catch (Throwable $e) {
                report($e);
                $result = HealthResult::error(__('Check failed: :message', ['message' => class_basename($e)]));
            }

            return ['check' => $check, 'result' => $result];
        }, $checks);
    }
}
