<?php

declare(strict_types=1);

namespace App\Student;

/**
 * Validates and normalises raw registration input.
 *
 * Pure logic, no I/O - the easiest kind of code to unit-test, and the first
 * safety net the CI pipeline runs on every push.
 */
final class StudentValidator
{
    public const COURSES = [
        'Computer Science',
        'Information Technology',
        'Electronics',
        'Mechanical',
        'Civil',
        'Business Administration',
    ];

    /**
     * @param array<mixed> $input
     * @return array{
     *     data: array{full_name: string, email: string, phone: ?string, course: string},
     *     errors: array<string, string>
     * }
     */
    public function validate(array $input): array
    {
        $errors = [];

        $fullName = preg_replace('/\s+/', ' ', self::string($input, 'full_name')) ?? '';
        if ($fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        } elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors['full_name'] = 'Full name must be between 2 and 100 characters.';
        } elseif (preg_match("/^[\\p{L} .'-]+$/u", $fullName) !== 1) {
            $errors['full_name'] = 'Full name may only contain letters, spaces, dots, hyphens and apostrophes.';
        }

        $email = strtolower(self::string($input, 'email'));
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email address is not valid.';
        }

        $phone = self::string($input, 'phone');
        if ($phone !== '' && preg_match('/^\+?[0-9 ()-]{7,20}$/', $phone) !== 1) {
            $errors['phone'] = 'Phone number is not valid.';
        }

        $course = self::string($input, 'course');
        if ($course === '') {
            $errors['course'] = 'Course is required.';
        } elseif (!in_array($course, self::COURSES, true)) {
            $errors['course'] = 'Please choose a course from the list.';
        }

        return [
            'data' => [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'course' => $course,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * @param array<mixed> $input
     */
    private static function string(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
