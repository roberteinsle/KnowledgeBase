<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The api guard (MCP) needs Passport keys even to reject a request.
        // In production docker/entrypoint.d/45-passport-keys.sh creates them;
        // CI and fresh checkouts have none.
        if (! is_file(storage_path('oauth-private.key')) || ! is_file(storage_path('oauth-public.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }
    }
}
