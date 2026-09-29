<?php

declare(strict_types=1);

namespace App\Student;

use App\Http\HttpClient;
use App\Http\HttpResult;
use RuntimeException;

/**
 * Talks to Supabase through its auto-generated REST API (PostgREST).
 * Docs: https://supabase.com/docs/guides/api
 */
final class SupabaseStudentRepository implements StudentRepository
{
    private const TABLE_PATH = '/rest/v1/students';

    // Only these columns are ever returned to the browser. Email and phone
    // are personal data, so they stay inside the database.
    private const PUBLIC_COLUMNS = 'id,full_name,course,created_at';

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {
    }

    public function create(array $student): array
    {
        $result = $this->send(
            'POST',
            $this->baseUrl . self::TABLE_PATH . '?select=' . self::PUBLIC_COLUMNS,
            ['Prefer' => 'return=representation'],
            json_encode($student, JSON_THROW_ON_ERROR),
        );

        // 409 = Postgres unique_violation on the email column.
        if ($result->status === 409) {
            throw new DuplicateEmailException('Email already registered');
        }

        $rows = $result->json();
        if ($result->status !== 201 || !is_array($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            throw new RepositoryException(sprintf('Supabase insert failed with HTTP %d', $result->status));
        }

        return $rows[0];
    }

    public function listRecent(int $limit): array
    {
        $query = http_build_query([
            'select' => self::PUBLIC_COLUMNS,
            'order' => 'created_at.desc',
            'limit' => $limit,
        ]);

        $result = $this->send('GET', $this->baseUrl . self::TABLE_PATH . '?' . $query);

        $rows = $result->json();
        if ($result->status !== 200 || !is_array($rows) || !array_is_list($rows)) {
            throw new RepositoryException(sprintf('Supabase select failed with HTTP %d', $result->status));
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    private function send(string $method, string $url, array $extraHeaders = [], ?string $body = null): HttpResult
    {
        $headers = [
            'apikey' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ] + $extraHeaders;

        // Legacy Supabase keys (service_role) are JWTs and must also be sent as
        // a Bearer token. The newer "sb_secret_..." keys only need the apikey header.
        if (str_starts_with($this->apiKey, 'eyJ')) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        try {
            return $this->http->request($method, $url, $headers, $body);
        } catch (RuntimeException $e) {
            throw new RepositoryException('Could not reach Supabase: ' . $e->getMessage(), 0, $e);
        }
    }
}
