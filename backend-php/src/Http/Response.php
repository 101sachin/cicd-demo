<?php

declare(strict_types=1);

namespace App\Http;

/**
 * An immutable JSON response. Controllers RETURN a Response instead of
 * echoing output, which makes them easy to unit-test.
 */
final class Response
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        public readonly int $status,
        public readonly array $body,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function json(int $status, array $body): self
    {
        return new self($status, $body);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($this->body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
