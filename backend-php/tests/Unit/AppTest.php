<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\App;
use App\Config;
use App\Student\StudentController;
use App\Student\StudentValidator;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryStudentRepository;

final class AppTest extends TestCase
{
    public function testHealthReportsVersion(): void
    {
        $response = $this->app()->handle('GET', '/api/health', '');

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body['status']);
        self::assertSame('abc123', $response->body['version']);
        self::assertSame('configured', $response->body['database']);
    }

    public function testCreatesStudentFromJsonBody(): void
    {
        $body = json_encode([
            'full_name' => 'Asha Verma',
            'email' => 'asha@example.com',
            'course' => 'Electronics',
        ], JSON_THROW_ON_ERROR);

        $response = $this->app()->handle('POST', '/api/students', $body);

        self::assertSame(201, $response->status);
    }

    public function testTrailingSlashAndQueryStringAreIgnored(): void
    {
        $response = $this->app()->handle('GET', '/api/students/?page=1', '');

        self::assertSame(200, $response->status);
    }

    public function testInvalidJsonIsBadRequest(): void
    {
        $response = $this->app()->handle('POST', '/api/students', '{not json');

        self::assertSame(400, $response->status);
    }

    public function testJsonThatIsNotAnObjectIsBadRequest(): void
    {
        $response = $this->app()->handle('POST', '/api/students', '"hello"');

        self::assertSame(400, $response->status);
    }

    public function testUnknownRouteIsNotFound(): void
    {
        self::assertSame(404, $this->app()->handle('GET', '/api/teachers', '')->status);
    }

    public function testWrongMethodIsNotAllowed(): void
    {
        self::assertSame(405, $this->app()->handle('DELETE', '/api/students', '')->status);
    }

    public function testStudentsEndpointWithoutDatabaseIsUnavailable(): void
    {
        $app = new App(new Config('', '', 'dev', 'test'), null);

        self::assertSame(503, $app->handle('GET', '/api/students', '')->status);
        self::assertSame('not_configured', $app->handle('GET', '/api/health', '')->body['database']);
    }

    private function app(): App
    {
        return new App(
            new Config('https://example.supabase.co', 'test-key', 'abc123', 'test'),
            new StudentController(new InMemoryStudentRepository(), new StudentValidator()),
        );
    }
}
