<?php

namespace Ludelix\Interface\ORM;

interface EventManagerInterface
{
    public function addEventListener(string $eventName, callable $listener): void;
    public function removeEventListener(string $eventName, callable $listener): void;
    public function dispatchEvent(object $event): void;
    public function hasListeners(string $eventName): bool;
    public function getListeners(string $eventName = null): array;
}