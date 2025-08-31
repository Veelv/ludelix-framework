<?php

namespace Ludelix\Core\Console\Extensions\Contracts;

interface CommandProviderInterface
{
    /**
     * Get commands provided by this extension
     * 
     * @return array Array of command name => class mappings
     */
    public function commands(): array;

    /**
     * Get command namespace
     */
    public function namespace(): string;

    /**
     * Get command aliases
     */
    public function aliases(): array;

    /**
     * Get command descriptions
     */
    public function descriptions(): array;
}