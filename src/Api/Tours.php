<?php

namespace Ammonkc\Ptpkg\Api;

use Ammonkc\Ptpkg\Exception\MissingArgumentException;

/**
 * Listing tours, showing, and updating
 *
 * @link   https://ptpkg.com/api/v1/tours/
 *
 * @author Ammon Casey <ammon@caseyohana.com>
 */
class Tours extends AbstractApi
{
    /**
     * Endpoint
     *
     * @var string
     */
    protected $endpoint = '/tours';

    /**
     * {@inheritdoc}
     */
    protected function getEndpoint()
    {
        return $this->endpoint_base . $this->endpoint;
    }

    /**
     * List tours
     *
     * @link https://ptpkg.com/api/v1/tours/
     *
     * @param array  $params     the additional parameters
     *
     * @return array list of tours found
     */
    public function all(array $params = [])
    {
        return $this->get($this->getEndpoint(), array_merge(['page' => 1], $params));
    }

    /**
     * Get extended information about a tour by its id
     *
     * @link https://ptpkg.com/api/v1/tours/
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
     * Get extended information about a tour by its id
     *
     * @link https://ptpkg.com/api/v1/tours/
     *
     * @param int    $id         the tour number
     *
     * @return array information about the tour
     */
    public function show_wp(int $id)
    {
        return $this->get($this->getEndpoint() . '/' . rawurlencode($id) . '/wp');
    }

    /**
     * Get status information about a tour by its id
     *
     * @link https://ptpkg.com/api/v1/tours/
     *
     * @param int    $id         the tour number
     *
     * @return array information about the tour
     */
    public function status(int $id)
    {
        return $this->get($this->getEndpoint() . '/' . rawurlencode($id) . '/status');
    }

    /**
     * Get status information about a tour by its wp_id
     *
     * @link https://ptpkg.com/api/v1/tours/
     *
     * @param int    $id         the tour number
     *
     * @return array information about the tour
     */
    public function status_wp(int $id)
    {
        return $this->get($this->getEndpoint() . '/' . rawurlencode($id) . '/status/wp');
    }

    /**
     * Create a new tour
     *
     * @link https://ptpkg.com/api/v1/tours/
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
     * @link https://ptpkg.com/api/v1/tours/
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
