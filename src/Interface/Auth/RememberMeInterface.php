<?php
namespace Ludelix\Interface\Auth;

interface RememberMeInterface
{
    public function getRememberToken(): ?string;
    public function setRememberToken(string $token): void;
    public function clearRememberToken(): void;
} 