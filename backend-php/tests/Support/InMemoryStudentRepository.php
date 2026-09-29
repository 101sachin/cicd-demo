<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Student\DuplicateEmailException;
use App\Student\StudentRepository;

/**
 * A fake database for unit tests. It behaves like Supabase (unique email,
 * auto-increment id) but lives in memory, so tests are fast and need no network.
 */
final class InMemoryStudentRepository implements StudentRepository
{
    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public function create(array $student): array
    {
        foreach ($this->rows as $row) {
            if ($row['email'] === $student['email']) {
                throw new DuplicateEmailException('Email already registered');
            }
        }

        $row = ['id' => count($this->rows) + 1] + $student + ['created_at' => '2026-01-01T00:00:00+00:00'];
        $this->rows[] = $row;

        return ['id' => $row['id'], 'full_name' => $row['full_name'], 'course' => $row['course']];
    }

    public function listRecent(int $limit): array
    {
        return array_slice(array_reverse($this->rows), 0, $limit);
    }
}
