<?php

namespace Ludelix\Auth\Core\Event\Events;

use Ludelix\Interface\Auth\UserInterface;

/**
 * PasswordResetEvent - Represents a user password reset event
 * 
 * This event is dispatched when a user successfully resets their password.
 * 
 * @package Ludelix\Auth\Core\Event\Events
 */
class PasswordResetEvent
{
    /**
     * The user who reset their password
     *
     * @var UserInterface
     */
    public UserInterface $user;

    /**
     * PasswordResetEvent constructor.
     *
     * @param UserInterface $user The user who reset their password
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }
}