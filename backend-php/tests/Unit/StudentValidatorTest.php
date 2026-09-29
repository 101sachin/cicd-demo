<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Student\StudentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StudentValidatorTest extends TestCase
{
    private StudentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StudentValidator();
    }

    public function testValidInputIsNormalised(): void
    {
        $result = $this->validator->validate([
            'full_name' => '  Asha   Verma ',
            'email' => ' Asha.Verma@Example.COM ',
            'phone' => '+91 98765 43210',
            'course' => 'Computer Science',
        ]);

        self::assertSame([], $result['errors']);
        self::assertSame('Asha Verma', $result['data']['full_name']);
        self::assertSame('asha.verma@example.com', $result['data']['email']);
        self::assertSame('+91 98765 43210', $result['data']['phone']);
    }

    public function testPhoneIsOptional(): void
    {
        $result = $this->validator->validate([
            'full_name' => 'Ravi Kumar',
            'email' => 'ravi@example.com',
            'phone' => '',
            'course' => 'Civil',
        ]);

        self::assertSame([], $result['errors']);
        self::assertNull($result['data']['phone']);
    }

    public function testEmptyInputReportsEveryRequiredField(): void
    {
        $result = $this->validator->validate([]);

        self::assertSame(['full_name', 'email', 'course'], array_keys($result['errors']));
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidFieldProvider')]
    public function testInvalidFieldIsRejected(array $override, string $expectedErrorField): void
    {
        $input = array_merge([
            'full_name' => 'Ravi Kumar',
            'email' => 'ravi@example.com',
            'phone' => '9876543210',
            'course' => 'Civil',
        ], $override);

        $result = $this->validator->validate($input);

        self::assertArrayHasKey($expectedErrorField, $result['errors']);
        self::assertCount(1, $result['errors']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidFieldProvider(): array
    {
        return [
            'name too short' => [['full_name' => 'A'], 'full_name'],
            'name with digits' => [['full_name' => 'R2D2'], 'full_name'],
            'name with html' => [['full_name' => '<script>'], 'full_name'],
            'bad email' => [['email' => 'not-an-email'], 'email'],
            'bad phone' => [['phone' => 'call me'], 'phone'],
            'unknown course' => [['course' => 'Astrology'], 'course'],
            'non-string email' => [['email' => ['array']], 'email'],
        ];
    }
}
