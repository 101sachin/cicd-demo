<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Student\StudentController;
use App\Student\StudentValidator;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryStudentRepository;

final class StudentControllerTest extends TestCase
{
    private InMemoryStudentRepository $repository;
    private StudentController $controller;

    protected function setUp(): void
    {
        $this->repository = new InMemoryStudentRepository();
        $this->controller = new StudentController($this->repository, new StudentValidator());
    }

    public function testRegistersValidStudent(): void
    {
                $response = $this->controller->register($this->validInput());
                // access the response body and status code should be 201
        self::assertSame(201, $response->status);
                    self::assertSame(
                        'Asha Verma',
                        $response->body['student']['full_name']
                    );
        self::assertCount(1, $this->repository->rows);
    }

    public function testRejectsInvalidInputWithoutTouchingTheDatabase(): void
    {
        $response = $this->controller->register(['email' => 'nope']);

        self::assertSame(422, $response->status);
        self::assertArrayHasKey('email', $response->body['errors']);
        self::assertSame([], $this->repository->rows);
    }

    public function testDuplicateEmailReturnsConflict(): void
    {
        $this->controller->register($this->validInput());
        $response = $this->controller->register($this->validInput());

        self::assertSame(409, $response->status);
        self::assertArrayHasKey('email', $response->body['errors']);
    }

    public function testListsStudentsNewestFirst(): void
    {
        $this->controller->register($this->validInput());
        $this->controller->register(['email' => 'ravi@example.com', 'full_name' => 'Ravi Kumar'] + $this->validInput());

        $response = $this->controller->list();

        self::assertSame(200, $response->status);
        self::assertSame('Ravi Kumar', $response->body['students'][0]['full_name']);
    }

    /**
     * @return array<string, string>
     */
    private function validInput(): array
    {
        return [
            'full_name' => 'Asha Verma',
            'email' => 'asha@example.com',
            'phone' => '',
            'course' => 'Computer Science',
        ];
    }
}
