<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\HttpClient;
use App\Http\HttpResult;

/**
 * Records outgoing requests and replays a canned response.
 */
final class FakeHttpClient implements HttpClient
{
    /** @var list<array{method: string, url: string, headers: array<string, string>, body: ?string}> */
    public array $requests = [];

    public function __construct(private readonly HttpResult|\RuntimeException $next)
    {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResult
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

        if ($this->next instanceof \RuntimeException) {
            throw $this->next;
        }

        return $this->next;
    }
}
