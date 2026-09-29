<?php

declare(strict_types=1);

namespace App\Http;

/**
 * A tiny abstraction over outgoing HTTP calls.
 *
 * Production uses CurlHttpClient; unit tests use a fake, so the CI pipeline
 * never needs a real database or network access to run the tests.
 */
interface HttpClient
{
    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResult;
}
