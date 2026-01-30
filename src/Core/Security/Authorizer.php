<?php

namespace Ludelix\Core\Security;

/**
 * Authorizer
 * 
 * Handles user authorization and permissions
 */
class Authorizer
{
    protected array $policies = [];
    protected array $roles = [];
    protected array $permissions = [];

    /**
     * Define policy
     */
    public function define(string $ability, callable $callback): void
    {
        $this->policies[$ability] = $callback;
    }

    /**
     * Check if user can perform ability
     */
    public function can(?array $user, string $ability, mixed $resource = null): bool
    {
        if (!$user) {
            return false;
        }

        // Check policy
        if (isset($this->policies[$ability])) {
            return $this->policies[$ability]($user, $resource);
        }

        // Check role-based permissions
        return $this->checkRolePermission($user, $ability);
    }

    /**
     * Check if user cannot perform ability
     */
    public function cannot(?array $user, string $ability, mixed $resource = null): bool
    {
        return !$this->can($user, $ability, $resource);
    }

    /**
     * Authorize or throw exception
     */
    public function authorize(?array $user, string $ability, mixed $resource = null): void
    {
        if ($this->cannot($user, $ability, $resource)) {
            throw new \RuntimeException("Unauthorized action: {$ability}");
        }
    }

    /**
     * Check role permission
     */
    protected function checkRolePermission(array $user, string $ability): bool
    {
        $userRoles = $user['roles'] ?? [];
        
        foreach ($userRoles as $role) {
            if (isset($this->roles[$role]) && in_array($ability, $this->roles[$role])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Define role with permissions
     */
    public function defineRole(string $role, array $permissions): void
    {
        $this->roles[$role] = $permissions;
    }

    /**
     * Check if user has role
     */
    public function hasRole(?array $user, string $role): bool
    {
        if (!$user) {
            return false;
        }

        return in_array($role, $user['roles'] ?? []);
    }

    /**
     * Check if user has any role
     */
    public function hasAnyRole(?array $user, array $roles): bool
    {
        if (!$user) {
            return false;
        }

        $userRoles = $user['roles'] ?? [];
        return !empty(array_intersect($userRoles, $roles));
    }

    /**
     * Check if user has all roles
     */
    public function hasAllRoles(?array $user, array $roles): bool
    {
        if (!$user) {
            return false;
        }

        $userRoles = $user['roles'] ?? [];
        return empty(array_diff($roles, $userRoles));
    }

    /**
     * Get user permissions
     */
    public function getPermissions(?array $user): array
    {
        if (!$user) {
            return [];
        }

        $permissions = [];
        $userRoles = $user['roles'] ?? [];

        foreach ($userRoles as $role) {
            if (isset($this->roles[$role])) {
                $permissions = array_merge($permissions, $this->roles[$role]);
            }
        }

        return array_unique($permissions);
    }
}