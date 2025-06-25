<?php

namespace Ludelix\Database\Repositories;

class UserRepository extends BaseRepository
{
    public function findByEmail(string $email): ?object
    {
        return $this->findBy(['email' => $email])[0] ?? null;
    }
    
    public function findActive(): array
    {
        return $this->findBy(['active' => true]);
    }
}