<?php

declare(strict_types=1);

namespace App\Student;

/**
 * Storage contract. The controller depends on this interface, not on
 * Supabase directly, so tests can swap in an in-memory implementation.
 */
interface StudentRepository
{
    /**
     * @param array{full_name: string, email: string, phone: ?string, course: string} $student
     * @return array<string, mixed> The stored student (public fields only)
     *
     * @throws DuplicateEmailException
     * @throws RepositoryException
     */
    public function create(array $student): array;

    /**
     * @return list<array<string, mixed>>
     *
     * @throws RepositoryException
     */
    public function listRecent(int $limit): array;
}
