<?php

namespace Ammonkc\Ptpkg\Api;

use Ammonkc\Ptpkg\Exception\MissingArgumentException;

/**
 * Listing users, showing, and updating
 *
 * @link   https://ptpkg.dev/api/v1/users/
 *
 * @author Ammon Casey <ammon@caseyohana.com>
 */
class Users extends AbstractApi
{
    /**
     * Endpoint
     *
     * @var string
     */
    protected $endpoint = '/user';

    /**
     * {@inheritdoc}
     */
    protected function getEndpoint()
    {
        return $this->endpoint_base . $this->endpoint;
    }

    /**
     * List users
     *
     * @link https://ptpkg.dev/api/v1/users/
     *
     * @param array  $params     the additional parameters
     *
     * @return array list of users found
     */
    public function all(array $params = [])
    {
        return $this->get($this->getEndpoint(), array_merge(['page' => 1], $params));
    }

    /**
     * Get extended information about a tour by its id
     *
     * @link https://ptpkg.dev/api/v1/user/
     *
     * @param int    $id         the tour number
     *
     * @return array information about the tour
     */
    public function me()
    {
        return $this->get($this->getEndpoint());
    }

    /**
     * Get extended information about a tour by its id
     *
     * @link https://ptpkg.dev/api/v1/users/
     *
     * @param int    $id         the tour number
     *
     * @return array information about the tour
     */
    public function show(int $id)
    {
        return $this->get($this->getEndpoint() . '/' . rawurlencode($id));
    }

    /**
     * Create a new tour
     *
     * @link https://ptpkg.dev/api/v1/users/
     *
     * @param array  $params     the new tour data
     *
     * @throws MissingArgumentException
     *
     * @return array information about the tour
     */
    public function create(array $params)
    {
        return $this->post($this->getEndpoint(), $params);
    }

    /**
     * Update tour information's by id. Requires authentication.
     *
     * @link https://ptpkg.dev/api/v1/users/
     *
     * @param int    $id         the issue number
     * @param array  $params     key=>value user attributes to update.
     *                           key can be title or body
     *
     * @return array information about the issue
     */
    public function update(int $id, array $params)
    {
        return $this->patch($this->getEndpoint() . '/' . rawurlencode($id), $params);
    }
}
