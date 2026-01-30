<?php

namespace Ludelix\Database\Repositories;

/**
 * Repository specifically for User entities.
 */
class UserRepository extends BaseRepository
{
    /**
     * Finds a user by email.
     *
     * @param string $email
     * @return object|null
     */
    public function findByEmail(string $email): ?object
    {
        return $this->findBy(['email' => $email])[0] ?? null;
    }

    /**
     * Finds all active users.
     *
     * @return array
     */
    public function findActive(): array
    {
        return $this->findBy(['active' => true]);
    }
}