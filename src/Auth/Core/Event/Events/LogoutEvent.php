<?php

namespace Ludelix\Auth\Core\Event\Events;

use Ludelix\Interface\Auth\UserInterface;

/**
 * LogoutEvent - Represents a user logout event
 * 
 * This event is dispatched when a user logs out of the application.
 * 
 * @package Ludelix\Auth\Core\Event\Events
 */
class LogoutEvent
{
    /**
     * The user who logged out
     *
     * @var UserInterface
     */
    public UserInterface $user;

    /**
     * LogoutEvent constructor.
     *
     * @param UserInterface $user The user who logged out
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }
}