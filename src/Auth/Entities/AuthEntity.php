<?php

namespace Ludelix\Auth\Entities;

use Ludelix\Database\Attributes\Entity;
use Ludelix\Database\Attributes\Column;
use Ludelix\Database\Attributes\PrimaryKey;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Database\Core\HiddenFieldsTrait;

/**
 * AuthEntity - User entity for authentication
 * 
 * This entity represents a user in the system and implements the UserInterface
 * for use with the authentication system.
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
    public ?string $avatar = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $role = null;

    // --- Advanced ---
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
     * Fields that should be hidden when serializing
     *
     * @var array
     */
    protected array $hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes'];

    /**
     * Get the user's authentication identifier
     *
     * @return int|string
     */
    public function getAuthIdentifier(): int|string { return $this->id; }

    /**
     * Get the user's password
     *
     * @return string|null
     */
    public function getAuthPassword(): ?string { return $this->password; }

    /**
     * Get the user's email
     *
     * @return string|null
     */
    public function getAuthEmail(): ?string { return $this->email; }

    /**
     * Get the user's avatar URL
     *
     * @return string|null
     */
    public function getAvatarUrl(): ?string { return $this->avatar; }

    /**
     * Get the user's role
     *
     * @return string|null
     */
    public function getRole(): ?string { return $this->role; }

    /**
     * Get the user's permissions
     *
     * @return array
     */
    public function getPermissions(): array { return $this->permissions; }

    /**
     * Get the user's two-factor authentication secret
     *
     * @return string|null
     */
    public function getTwoFactorSecret(): ?string { return $this->two_factor_secret; }

    /**
     * Get the user's two-factor recovery codes
     *
     * @return string|null
     */
    public function getTwoFactorRecoveryCodes(): ?string { return $this->two_factor_recovery_codes; }

    /**
     * Check if the user's email is verified
     *
     * @return bool
     */
    public function isEmailVerified(): bool { return !empty($this->email_verified_at); }

    /**
     * Check if the user is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool { return !empty($this->deleted_at); }

    /**
     * Get the user's creation timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string { return $this->created_at; }

    /**
     * Get the user's last update timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    
    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}