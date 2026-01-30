<?php
namespace Ludelix\Interface\Auth;

interface UserProviderInterface
{
    /**
     * Recupera um usuário pelo ID único.
     * @param int|string $id
     * @return UserInterface|null
     */
    public function retrieveById(int|string $id): ?UserInterface;

    /**
     * Recupera um usuário pelas credenciais (ex: email, username).
     * @param array $credentials
     * @return UserInterface|null
     */
    public function retrieveByCredentials(array $credentials): ?UserInterface;

    /**
     * Valida as credenciais do usuário.
     * @param UserInterface $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(UserInterface $user, array $credentials): bool;
} 