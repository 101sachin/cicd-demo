<?php

declare(strict_types=1);

namespace App;

use App\Http\Response;
use App\Student\RepositoryException;
use App\Student\StudentController;
use JsonException;
use Throwable;

/**
 * The router. It maps "METHOD /path" to a controller and turns any
 * unexpected error into a clean JSON response.
 *
 * API contract (the Python backend will implement exactly the same one):
 *   GET  /api/health    -> 200 service status + deployed version
 *   GET  /api/students  -> 200 recently registered students
 *   POST /api/students  -> 201 created | 400 bad JSON | 409 duplicate | 422 invalid
 */
final class App
{
    public function __construct(
        private readonly Config $config,
        private readonly ?StudentController $students,
    ) {
    }

    public function handle(string $method, string $uri, string $rawBody): Response
    {
        $path = rtrim((string) parse_url($uri, PHP_URL_PATH), '/');

        try {
            return match ($path) {
                '/api/health' => $method === 'GET' ? $this->health() : $this->methodNotAllowed(),
                '/api/students' => $this->students($method, $rawBody),
                default => Response::json(404, ['error' => 'Not found']),
            };
        } catch (RepositoryException $e) {
            error_log('[repository] ' . $e->getMessage());

            return Response::json(502, ['error' => 'The database is temporarily unavailable. Please try again.']);
        } catch (Throwable $e) {
            error_log('[unhandled] ' . $e::class . ': ' . $e->getMessage());

            return Response::json(500, ['error' => 'Internal server error']);
        }
    }

    private function health(): Response
    {
        // CI/CD lesson: after a deploy, the pipeline calls this endpoint and
        // checks that "version" equals the git commit it just shipped.
        return Response::json(200, [
            'status' => 'ok',
            'service' => 'student-api-php',
            'version' => $this->config->appVersion,
            'environment' => $this->config->appEnv,
            'database' => $this->config->isDatabaseConfigured() ? 'configured' : 'not_configured',
        ]);
    }

    private function students(string $method, string $rawBody): Response
    {
        if ($method !== 'GET' && $method !== 'POST') {
            return $this->methodNotAllowed();
        }

        if ($this->students === null) {
            return Response::json(503, ['error' => 'Database is not configured on this server']);
        }

        if ($method === 'GET') {
            return $this->students->list();
        }

        try {
            $input = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return Response::json(400, ['error' => 'Request body must be valid JSON']);
        }

        if (!is_array($input)) {
            return Response::json(400, ['error' => 'Request body must be a JSON object']);
        }

        return $this->students->register($input);
    }

    private function methodNotAllowed(): Response
    {
        return Response::json(405, ['error' => 'Method not allowed']);
    }
}
