<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\HttpResult;
use App\Student\DuplicateEmailException;
use App\Student\RepositoryException;
use App\Student\SupabaseStudentRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\FakeHttpClient;

final class SupabaseStudentRepositoryTest extends TestCase
{
    private const STUDENT = [
        'full_name' => 'Asha Verma',
        'email' => 'asha@example.com',
        'phone' => null,
        'course' => 'Civil',
    ];

    public function testCreateSendsCorrectRequest(): void
    {
        $http = new FakeHttpClient(new HttpResult(201, '[{"id":7,"full_name":"Asha Verma","course":"Civil"}]'));
        $repository = new SupabaseStudentRepository($http, 'https://demo.supabase.co', 'sb_secret_abc');

        $student = $repository->create(self::STUDENT);

        self::assertSame(7, $student['id']);
        $request = $http->requests[0];
        self::assertSame('POST', $request['method']);
        self::assertStringStartsWith('https://demo.supabase.co/rest/v1/students?select=', $request['url']);
        self::assertSame('sb_secret_abc', $request['headers']['apikey']);
        self::assertSame('return=representation', $request['headers']['Prefer']);
        self::assertArrayNotHasKey('Authorization', $request['headers']);
        self::assertSame(self::STUDENT, json_decode((string) $request['body'], true));
    }

    public function testLegacyJwtKeyIsAlsoSentAsBearerToken(): void
    {
        $http = new FakeHttpClient(new HttpResult(200, '[]'));
        $repository = new SupabaseStudentRepository($http, 'https://demo.supabase.co', 'eyJhbGciOi.fake.jwt');

        $repository->listRecent(10);

        self::assertSame('Bearer eyJhbGciOi.fake.jwt', $http->requests[0]['headers']['Authorization']);
    }

    public function testListRequestsOnlyPublicColumnsNewestFirst(): void
    {
        $http = new FakeHttpClient(new HttpResult(200, '[{"id":1,"full_name":"Asha Verma"}]'));
        $repository = new SupabaseStudentRepository($http, 'https://demo.supabase.co', 'key');

        $rows = $repository->listRecent(25);

        self::assertCount(1, $rows);
        $url = $http->requests[0]['url'];
        self::assertStringContainsString('order=created_at.desc', $url);
        self::assertStringContainsString('limit=25', $url);
        self::assertStringNotContainsString('email', $url);
        self::assertStringNotContainsString('phone', $url);
    }

    public function testConflictBecomesDuplicateEmailException(): void
    {
        $http = new FakeHttpClient(new HttpResult(409, '{"code":"23505"}'));
        $repository = new SupabaseStudentRepository($http, 'https://demo.supabase.co', 'key');

        $this->expectException(DuplicateEmailException::class);
        $repository->create(self::STUDENT);
    }

    public function testServerErrorBecomesRepositoryException(): void
    {
        $http = new FakeHttpClient(new HttpResult(500, '{"message":"boom"}'));
        $repository = new SupabaseStudentRepository($http, 'https://demo.supabase.co', 'key');

        $this->expectException(RepositoryException::class);
        $repository->listRecent(10);
    }

    public function testNetworkFailureBecomesRepositoryException(): void
    {
        $http = new FakeHttpClient(new RuntimeException('Connection timed out'));
        $repository = new SupabaseStudentRepository($http, 'https://demo.supabase.co', 'key');

        $this->expectException(RepositoryException::class);
        $repository->create(self::STUDENT);
    }
}
