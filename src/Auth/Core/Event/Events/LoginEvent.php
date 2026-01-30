<?php

namespace Ludelix\Auth\Core\Event\Events;

use Ludelix\Interface\Auth\UserInterface;

/**
 * LoginEvent - Represents a user login event
 * 
 * This event is dispatched when a user successfully logs into the application.
 * 
 * @package Ludelix\Auth\Core\Event\Events
 */
class LoginEvent
{
    /**
     * The user who logged in
     *
     * @var UserInterface
     */
    public UserInterface $user;

    /**
     * LoginEvent constructor.
     *
     * @param UserInterface $user The user who logged in
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }
}