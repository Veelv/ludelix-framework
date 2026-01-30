<?php

namespace Ludelix\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use Ludelix\Core\Security\JwtService;
use Ludelix\Auth\Core\JwtGuard;
use Ludelix\Interface\Auth\UserInterface;
use Ludelix\Interface\Auth\UserProviderInterface;

// 1. Mock User
class JwtVerification implements UserInterface
{
    public function getId(): int|string
    {
        return 123;
    }
    public function getEmail(): string
    {
        return 'user@example.com';
    }
    public function getName(): string
    {
        return 'Test User';
    }
    public function getRoles(): array
    {
        return [];
    }
    public function hasRole(string $role): bool
    {
        return false;
    }
    public function getPermissions(): array
    {
        return [];
    }
    public function hasPermission(string $permission): bool
    {
        return false;
    }
    public function getRememberToken(): ?string
    {
        return null;
    }
    public function setRememberToken(string $token): void
    {
    }
    public function clearRememberToken(): void
    {
    }
    public function isTwoFactorEnabled(): bool
    {
        return false;
    }
    public function enableTwoFactor(string $secret): void
    {
    }
    public function disableTwoFactor(): void
    {
    }
    public function isTwoFactorVerified(): bool
    {
        return false;
    }
    public function isActive(): bool
    {
        return true;
    }
    public function getPasswordHash(): string
    {
        return 'hash';
    }
}

// 2. Mock Provider
$provider = new class implements UserProviderInterface {
    public function retrieveById($id): ?UserInterface
    {
        return new JwtVerification();
    }
    public function retrieveByCredentials(array $credentials): ?UserInterface
    {
        return null;
    }
    public function validateCredentials(UserInterface $user, array $credentials): bool
    {
        return true;
    }
    public function retrieveByToken($id, $token): ?UserInterface
    {
        return null;
    }
    public function updateRememberToken(UserInterface $user, string $token): void
    {
    }
    public function createUser(array $data): ?UserInterface
    {
        return null;
    }
};

// 3. Setup JWT Service
$secret = 'test-secret-at-least-32-chars-long-!!!';
$jwt = new JwtService($secret);

echo "--- Ludelix JWT Authentication Verification ---\n\n";

// Test 1: Token Generation
$user = new JwtVerification();
$token = $jwt->generate(['sub' => $user->getId(), 'email' => $user->getEmail()]);

echo "Test 1: Token Generation\n";
if (count(explode('.', $token)) === 3) {
    echo "[OK] Token generated successfully: " . substr($token, 0, 20) . "...\n";
} else {
    echo "[FAIL] Token generation failed.\n";
}

// Test 2: Token Validation (Valid)
$payload = $jwt->validate($token);
echo "\nTest 2: Token Validation (Valid)\n";
if ($payload && $payload['sub'] === 123) {
    echo "[OK] Token validated and payload recovered.\n";
} else {
    echo "[FAIL] Token validation failed.\n";
}

// Test 3: Token Validation (Tampered)
$tamperedToken = $token . "extra";
$tamperedPayload = $jwt->validate($tamperedToken);
echo "\nTest 3: Token Validation (Tampered)\n";
if ($tamperedPayload === null) {
    echo "[OK] Tampered token correctly rejected.\n";
} else {
    echo "[FAIL] Tampered token was accepted!\n";
}

// Test 4: Token Validation (Expired - Simulating)
echo "\nTest 4: Token Expiration\n";
$expiredToken = $jwt->generate(['sub' => 123], -10); // Expired 10 seconds ago
$expiredPayload = $jwt->validate($expiredToken);
if ($expiredPayload === null) {
    echo "[OK] Expired token correctly rejected.\n";
} else {
    echo "[FAIL] Expired token was accepted!\n";
}

// Test 5: JwtGuard Authentication Simulation
$guard = new JwtGuard($provider, $jwt);
// Mock the Authorization header in $_SERVER
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

echo "\nTest 5: JwtGuard authentication from header\n";
if ($guard->check() && $guard->user()->getId() === 123) {
    echo "[OK] Guard successfully authenticated user from Bearer header.\n";
} else {
    echo "[FAIL] Guard failed to authenticate user.\n";
}

echo "\n--- Verification Complete ---\n";