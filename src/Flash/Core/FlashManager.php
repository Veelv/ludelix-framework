<?php

namespace Ludelix\Flash\Core;

use Ludelix\Bridge\Bridge;
use Ludelix\Flash\Core\MessageBag;
use Ludelix\Session\Store;

/**
 * FlashManager - Main class for managing flash messages
 * 
 * This class provides methods to store, retrieve and manage flash messages
 * that are displayed to the user and automatically cleared after being shown.
 * 
 * @package Ludelix\Flash\Core
 */
class FlashManager
{
    /**
     * The message bag instance
     *
     * @var MessageBag
     */
    protected MessageBag $messages;

    /**
     * Session store instance
     *
     * @var Store|mixed
     */
    protected $session;

    /**
     * Session key for storing flash messages
     *
     * @var string
     */
    protected string $sessionKey = 'flash_messages';

    /**
     * FlashManager constructor.
     */
    public function __construct()
    {
        // Get session from Bridge
        $this->session = Bridge::session()->getStore();

        $flashMessages = $this->session->get($this->sessionKey, []);

        // Ensure we always pass an array to MessageBag
        if (!is_array($flashMessages)) {
            $flashMessages = [];
        }

        $this->messages = new MessageBag($flashMessages);
    }

    /**
     * Add an info message
     *
     * @param string $message
     * @return self
     */
    public function info(string $message): self
    {
        $this->messages->add('info', $message);
        $this->saveToSession();
        return $this;
    }

    /**
     * Add a success message
     *
     * @param string $message
     * @return self
     */
    public function success(string $message): self
    {
        $this->messages->add('success', $message);
        $this->saveToSession();
        return $this;
    }

    /**
     * Add a warning message
     *
     * @param string $message
     * @return self
     */
    public function warning(string $message): self
    {
        $this->messages->add('warning', $message);
        $this->saveToSession();
        return $this;
    }

    /**
     * Add an error message
     *
     * @param string $message
     * @return self
     */
    public function error(string $message): self
    {
        $this->messages->add('error', $message);
        $this->saveToSession();
        return $this;
    }

    /**
     * Add a message of any type
     *
     * @param string $type
     * @param string $message
     * @return self
     */
    public function add(string $type, string $message): self
    {
        $this->messages->add($type, $message);
        $this->saveToSession();
        return $this;
    }

    /**
     * Get all messages
     *
     * @return array
     */
    public function all(): array
    {
        return $this->messages->all();
    }

    /**
     * Get messages of a specific type
     *
     * @param string $type
     * @return array
     */
    public function get(string $type): array
    {
        return $this->messages->get($type);
    }

    /**
     * Check if there are messages of a specific type
     *
     * @param string $type
     * @return bool
     */
    public function has(string $type): bool
    {
        return $this->messages->has($type);
    }

    /**
     * Check if there are any messages
     *
     * @return bool
     */
    public function any(): bool
    {
        return $this->messages->any();
    }

    /**
     * Clear all messages
     *
     * @return void
     */
    public function clear(): void
    {
        $this->messages->clear();
        $this->removeFromSession();
    }

    /**
     * Load messages from session
     *
     * @return void
     */
    protected function loadFromSession(): void
    {
        if (isset($_SESSION[$this->sessionKey])) {
            $this->messages = unserialize($_SESSION[$this->sessionKey]);
        }
    }

    /**
     * Save messages to session
     *
     * @return void
     */
    protected function saveToSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$this->sessionKey] = serialize($this->messages);
        }
    }

    /**
     * Remove messages from session
     *
     * @return void
     */
    protected function removeFromSession(): void
    {
        if (isset($_SESSION[$this->sessionKey])) {
            unset($_SESSION[$this->sessionKey]);
        }
    }
}