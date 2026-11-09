<?php

namespace Tests;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests never reach the "terminating" phase, so run after-response jobs (bulk emails) at once.
        $this->app->make(Dispatcher::class)->withoutDispatchingAfterResponses();
    }
}
