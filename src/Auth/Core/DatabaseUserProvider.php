<?php

namespace Ludelix\Auth\Core;

use Ludelix\Interface\Auth\UserProviderInterface;
use Ludelix\Interface\Auth\UserInterface;

/**
 * DatabaseUserProvider - Retrieves users from the database using Ludelix ORM.
 */
class DatabaseUserProvider implements UserProviderInterface
{
    protected string $userModel;

    /**
     * @param string $userModel The fully qualified class name of the User model.
     */
    public function __construct(string $userModel = 'App\\Entities\\User')
    {
        $this->userModel = $userModel;
    }

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById(int|string $id): ?UserInterface
    {
        if (!class_exists($this->userModel))
            return null;
        return ($this->userModel)::find($id);
    }

    /**
     * Retrieve a user by their credentials (e.g., email).
     */
    public function retrieveByCredentials(array $credentials): ?UserInterface
    {
        if (!class_exists($this->userModel))
            return null;

        $query = ($this->userModel)::query();
        foreach ($credentials as $key => $value) {
            if ($key !== 'password') {
                $query->where($key, '=', $value);
            }
        }

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(UserInterface $user, array $credentials): bool
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        return password_verify($credentials['password'], $user->getPasswordHash());
    }

    /**
     * Create a new user (Placeholder for registration).
     */
    public function createUser(array $data): ?UserInterface
    {
        if (!class_exists($this->userModel))
            return null;
        $user = new ($this->userModel)();
        foreach ($data as $key => $value) {
            if ($key === 'password') {
                $user->password = password_hash($value, PASSWORD_BCRYPT);
            } else {
                $user->$key = $value;
            }
        }
        $user->save();
        return $user;
    }

    public function retrieveByToken(int|string $id, string $token): ?UserInterface
    {
        return null;
    }
    public function updateRememberToken(UserInterface $user, string $token): void
    {
    }
}
