<?php

namespace Ludelix\Interface\Console;

/**
 * Command Interface
 *
 * Defines the contract for all console commands in Mi.
 */
interface CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the command description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int Exit code
     */
    public function execute(array $args = []): int;

    /**
     * Get the command options
     *
     * @return array
     */
    public function getOptions(): array;
}
