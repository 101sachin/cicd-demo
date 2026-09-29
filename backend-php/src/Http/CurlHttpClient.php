<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class CurlHttpClient implements HttpClient
{
    public function __construct(private readonly int $timeoutSeconds = 10)
    {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResult
    {
        if ($method === '') {
            throw new RuntimeException('HTTP method must not be empty');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => $headerLines,
        ]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($handle);
        if (!is_string($responseBody)) {
            throw new RuntimeException('HTTP request failed: ' . curl_error($handle));
        }

        return new HttpResult((int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $responseBody);
    }
}
