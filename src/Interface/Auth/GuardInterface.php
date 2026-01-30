<?php
namespace Ludelix\Interface\Auth;

interface GuardInterface
{
    public function user(): ?UserInterface;
    public function check(): bool;
    public function login(UserInterface $user): void;
    public function logout(): void;
} 