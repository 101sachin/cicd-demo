<?php

declare(strict_types=1);

namespace App\Student;

use App\Http\Response;

final class StudentController
{
    private const LIST_LIMIT = 50;

    public function __construct(
        private readonly StudentRepository $repository,
        private readonly StudentValidator $validator,
    ) {
    }

    /**
     * POST /api/students
     *
     * @param array<mixed> $input
     */
    public function register(array $input): Response
    {
        $result = $this->validator->validate($input);

        if ($result['errors'] !== []) {
            return Response::json(422, [
                'error' => 'Validation failed',
                'errors' => $result['errors'],
            ]);
        }

        try {
            $student = $this->repository->create($result['data']);
        } catch (DuplicateEmailException) {
            return Response::json(409, [
                'error' => 'A student with this email is already registered',
                'errors' => ['email' => 'This email is already registered.'],
            ]);
        }

        return Response::json(201, [
            'message' => 'Student registered successfully',
            'student' => $student,
        ]);
    }

    /**
     * GET /api/students
     */
    public function list(): Response
    {
        $rs = Response::json(200, [
            'students' => $this->repository->listRecent(self::LIST_LIMIT),
        ]);
        //print_r($rs->body);
        return $rs;
    }
}
