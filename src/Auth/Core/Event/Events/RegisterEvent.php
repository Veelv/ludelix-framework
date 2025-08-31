<?php

namespace Ludelix\Auth\Core\Event\Events;

use Ludelix\Interface\Auth\UserInterface;

/**
 * RegisterEvent - Represents a user registration event
 * 
 * This event is dispatched when a new user successfully registers in the application.
 * 
 * @package Ludelix\Auth\Core\Event\Events
 */
class RegisterEvent
{
    /**
     * The user who registered
     *
     * @var UserInterface
     */
    public UserInterface $user;

    /**
     * RegisterEvent constructor.
     *
     * @param UserInterface $user The user who registered
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }
}