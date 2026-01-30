<?php

namespace Ludelix\Core\Support;

use Ludelix\Database\Query\QueryBuilder;
use Ludelix\PRT\Request;

/**
 * Base class for RESTful repositories.
 * Provides standard method signatures for REST operations and guides the correct use of the framework's request/response.
 *
 * Usage example:
 *   class UserRepository extends RestRepository { ... }
 *
 * To return JSON:
 *   return Bridge::response()::json([...]);
 */
abstract class RestRepository
{
    /**
     * Lists resources (GET /resource)
     *
     * @param Request $request
     * @param mixed $response
     * @return mixed
     */
    public function index(Request $request, $response)
    {
        // Implement resource listing
    }

    /**
     * Displays a specific resource (GET /resource/{id})
     *
     * @param Request $request
     * @param mixed $response
     * @param mixed $id
     * @return mixed
     */
    public function show(Request $request, $response, $id)
    {
        // Implement resource retrieval
    }

    /**
     * Creates a new resource (POST /resource)
     *
     * @param Request $request
     * @param mixed $response
     * @return mixed
     */
    public function store(Request $request, $response)
    {
        // Implement resource creation
    }

    /**
     * Updates a resource (PUT /resource/{id})
     *
     * @param Request $request
     * @param mixed $response
     * @param mixed $id
     * @return mixed
     */
    public function update(Request $request, $response, $id)
    {
        // Implement resource update
    }

    /**
     * Deletes a resource (DELETE /resource/{id})
     *
     * @param Request $request
     * @param mixed $response
     * @param mixed $id
     * @return mixed
     */
    public function destroy(Request $request, $response, $id)
    {
        // Implement resource deletion
    }

    /**
     * (Optional) Paginated listing, advanced search, etc.
     * Add extra methods as needed for your project.
     */
}