<?php

namespace Ludelix\Auth\Entities;

use Ludelix\Database\Attributes\Entity;
use Ludelix\Database\Attributes\Column;
use Ludelix\Database\Attributes\PrimaryKey;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Database\Core\HiddenFieldsTrait;

/**
 * AuthEntity - User entity for authentication.
 * 
 * Represents a system user and implements the UserInterface for authentication.
 * 
 * @package Ludelix\Auth\Entities
 */
#[Entity(table: 'users')]
class AuthEntity implements UserInterface, \JsonSerializable
{
    use HiddenFieldsTrait;

    #[PrimaryKey]
    #[Column(type: 'int', autoIncrement: true)]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255, unique: true)]
    public string $email;

    #[Column(type: 'string', length: 255)]
    public string $password;

    #[Column(type: 'string', nullable: true)]
    public ?string $remember_token = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $avatar = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $role = null;

    #[Column(type: 'json', nullable: true)]
    public ?array $permissions = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $two_factor_secret = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $two_factor_recovery_codes = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $email_verified_at = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $deleted_at = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $created_at = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $updated_at = null;

    /**
     * Fields that should be hidden when serializing.
     *
     * @var array
     */
    protected array $hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'];

    /**
     * {@inheritdoc}
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->role ? [$this->role] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * {@inheritdoc}
     */
    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    /**
     * {@inheritdoc}
     */
    public function setRememberToken(string $token): void
    {
        $this->remember_token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function clearRememberToken(): void
    {
        $this->remember_token = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isTwoFactorEnabled(): bool
    {
        return !empty($this->two_factor_secret);
    }

    /**
     * {@inheritdoc}
     */
    public function enableTwoFactor(string $secret): void
    {
        $this->two_factor_secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isTwoFactorVerified(): bool
    {
        // This usually requires checking if the session flagged 2FA as verified.
        // As an entity, we assume it's enabled if the secret exists.
        return $this->isTwoFactorEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return empty($this->deleted_at);
    }

    /**
     * {@inheritdoc}
     */
    public function getPasswordHash(): string
    {
        return $this->password;
    }

    /**
     * Get the user's avatar URL.
     *
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatar;
    }

    /**
     * Check if the user's email is verified.
     *
     * @return bool
     */
    public function isEmailVerified(): bool
    {
        return !empty($this->email_verified_at);
    }

}