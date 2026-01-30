<?php

namespace Ludelix\Flash\Core;

/**
 * MessageBag - Handles collections of flash messages
 * 
 * This class manages collections of messages organized by type.
 * 
 * @package Ludelix\Flash\Core
 */
class MessageBag
{
    /**
     * The messages in the bag
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * MessageBag constructor.
     *
     * @param array $messages
     */
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    /**
     * Add a message to the bag
     *
     * @param string $type
     * @param string $message
     * @return self
     */
    public function add(string $type, string $message): self
    {
        if (!isset($this->messages[$type])) {
            $this->messages[$type] = [];
        }

        $this->messages[$type][] = $message;

        return $this;
    }

    /**
     * Merge existing messages with new ones
     *
     * @param array $messages
     * @return self
     */
    public function merge(array $messages): self
    {
        $this->messages = array_merge_recursive($this->messages, $messages);
        return $this;
    }

    /**
     * Get all messages
     *
     * @return array
     */
    public function all(): array
    {
        return $this->messages;
    }

    /**
     * Get messages of a specific type
     *
     * @param string $type
     * @return array
     */
    public function get(string $type): array
    {
        return $this->messages[$type] ?? [];
    }

    /**
     * Check if messages of a specific type exist
     *
     * @param string $type
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($this->messages[$type]) && count($this->messages[$type]) > 0;
    }

    /**
     * Check if there are any messages
     *
     * @return bool
     */
    public function any(): bool
    {
        return count($this->messages) > 0;
    }

    /**
     * Get the first message of a specific type
     *
     * @param string $type
     * @param mixed $default
     * @return mixed
     */
    public function first(string $type, $default = null)
    {
        return $this->messages[$type][0] ?? $default;
    }

    /**
     * Get all messages flattened into a single array
     *
     * @return array
     */
    public function allFlattened(): array
    {
        $flattened = [];
        
        foreach ($this->messages as $type => $messages) {
            foreach ($messages as $message) {
                $flattened[] = ['type' => $type, 'message' => $message];
            }
        }
        
        return $flattened;
    }

    /**
     * Clear all messages
     *
     * @return void
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Get the number of messages
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }
}