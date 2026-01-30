<?php

namespace Ludelix\Interface;

use Ludelix\Interface\DI\ContainerInterface;

interface FrameworkInterface
{
    public function boot(): void;
    public function run(): void;
    public function terminate(): void;
    public function container(): ContainerInterface;
    public function version(): string;
    public function basePath(): string;
    public function configPath(): string;
    public function storagePath(): string;
    public function environment(): string;
    public function isProduction(): bool;
    public function isDebug(): bool;
}