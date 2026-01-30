<?php

namespace Ludelix\Session\Middleware;

use Ludelix\Session\SessionManager;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

class StartSession
{
    /**
     * The session manager.
     *
     * @var \Ludelix\Session\SessionManager
     */
    protected $manager;

    /**
     * Create a new session middleware.
     *
     * @param  \Ludelix\Session\SessionManager  $manager
     * @return void
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Ludelix\PRT\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, $next)
    {
        // We'll start the session and set the session on the request.
        $this->manager->getStore();

        // You might want to add the session to the request object here
        // e.g., $request->setSession($this->manager->getStore());

        $response = $next($request);

        // We'll save the session data and add the cookie to the response.
        $this->manager->getStore()->save();

        return $response;
    }
}
