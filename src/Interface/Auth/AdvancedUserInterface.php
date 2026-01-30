<?php
namespace Ludelix\Interface\Auth;

interface AdvancedUserInterface extends SimpleUserInterface
{
    public function getPermissions(): array;
    public function hasPermission(string $permission): bool;
    public function getAvatarUrl(): ?string;
    public function getCreatedAt(): ?\DateTimeInterface;
    public function getUpdatedAt(): ?\DateTimeInterface;
    // 2FA
    public function isTwoFactorEnabled(): bool;
    public function getTwoFactorSecret(): ?string;
    // API/JWT
    public function getApiTokens(): array;
} 