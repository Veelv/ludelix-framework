<?php
namespace Ludelix\Interface\Auth;

interface TwoFactorInterface
{
    public function getTwoFactorSecret(): ?string;
    public function setTwoFactorSecret(string $secret): void;
    public function clearTwoFactorSecret(): void;
    public function isTwoFactorEnabled(): bool;
    public function enableTwoFactor(string $secret): void;
    public function disableTwoFactor(): void;
    public function isTwoFactorVerified(): bool;
} 