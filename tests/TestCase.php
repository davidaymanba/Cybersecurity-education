<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must never reach a real AI provider (cost, rate limits, flaky results):
        // any HTTP call that is not faked fails the test.
        Http::preventStrayRequests();

        // Streaming uses Guzzle directly (not covered above), so also drop any real key from .env.
        // Tests that exercise a provider set their own fake key.
        config([
            'services.groq.api_key' => null,
            'services.groq.single_api_key' => null,
            'services.groq.multi_api_key' => null,
            'services.openai.api_key' => null,
        ]);
    }
}
