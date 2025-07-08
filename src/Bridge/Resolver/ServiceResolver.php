<?php
namespace Ludelix\Bridge\Resolver;

/**
 * Class ServiceResolver
 *
 * Responsible for resolving and retrieving services from the application container.
 * Provides methods to get, check, and list services for dependency injection scenarios.
 */
class ServiceResolver
{
    /**
     * The application service container instance.
     *
     * @var object|null
     */
    protected $container;

    /**
     * ServiceResolver constructor.
     *
     * @param object|null $container
     */
    public function __construct($container = null)
    {
        $this->container = $container;
    }

    /**
     * Get a service by its identifier.
     *
     * @param string $id
     * @return mixed|null
     */
    public function get(string $id)
    {
        if ($this->container && method_exists($this->container, 'get')) {
            return $this->container->get($id);
        }
        return null;
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        if ($this->container && method_exists($this->container, 'has')) {
            return $this->container->has($id);
        }
        return false;
    }

    /**
     * Get the underlying container instance.
     *
     * @return object|null
     */
    public function getContainer()
    {
        return $this->container;
    }
}
