<?php
namespace Ludelix\Interface\Auth;

interface RoleInterface
{
    public function getName(): string;
    public function getPermissions(): array;
} 