<?php

declare(strict_types=1);

namespace App\Http;

final class HttpResult
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
    ) {
    }

    public function json(): mixed
    {
        return json_decode($this->body, true);
    }
}
