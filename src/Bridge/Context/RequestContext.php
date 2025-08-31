<?php
namespace Ludelix\Bridge\Context;

/**
 * Class RequestContext
 *
 * Handles request-specific context information such as headers, parameters, and user data.
 * Provides methods to set and retrieve request data for the current lifecycle.
 */
class RequestContext
{
    /**
     * HTTP headers for the current request.
     *
     * @var array
     */
    protected array $headers = [];

    /**
     * Request parameters (GET, POST, etc).
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * Authenticated user data or session info.
     *
     * @var array
     */
    protected array $user = [];

    /**
     * Set request headers.
     *
     * @param array $headers
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Get request headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set request parameters.
     *
     * @param array $parameters
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Get request parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set user/session data.
     *
     * @param array $user
     * @return void
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * Get user/session data.
     *
     * @return array
     */
    public function getUser(): array
    {
        return $this->user;
    }

    /**
     * Reset the request context to its initial state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->headers = [];
        $this->parameters = [];
        $this->user = [];
    }
    
    /**
     * Create new instance with request
     */
    public function withRequest($request): self
    {
        $clone = clone $this;
        if (is_array($request)) {
            $clone->setParameters($request);
        }
        return $clone;
    }
}
