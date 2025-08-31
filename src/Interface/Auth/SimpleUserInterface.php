<?php
namespace Ludelix\Interface\Auth;

interface SimpleUserInterface
{
    public function getId(): int|string;
    public function getEmail(): string;
    public function getName(): ?string;
    public function getPasswordHash(): string;
    public function isActive(): bool;
    public function isVerified(): bool;
    public function getRoles(): array;
    public function hasRole(string $role): bool;
    public function toArray(): array;
} 