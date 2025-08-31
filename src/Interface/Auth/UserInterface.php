<?php
namespace Ludelix\Interface\Auth;

interface UserInterface
{
    public function getId(): int|string;
    public function getEmail(): string;
    public function getName(): string;
    public function getRoles(): array;
    public function hasRole(string $role): bool;
    public function getPermissions(): array;
    public function hasPermission(string $permission): bool;

    // Remember Me
    public function getRememberToken(): ?string;
    public function setRememberToken(string $token): void;
    public function clearRememberToken(): void;

    // 2FA
    public function isTwoFactorEnabled(): bool;
    public function enableTwoFactor(string $secret): void;
    public function disableTwoFactor(): void;
    public function isTwoFactorVerified(): bool;

    // Status
    public function isActive(): bool;
    public function getPasswordHash(): string;
} 