<?php

namespace Ammonkc\Ptpkg\Api;

/**
 * Listing users, showing, and updating
 *
 * @link   https://ptpkg.com/api/v1/users/
 *
 * @author Ammon Casey <ammon@caseyohana.com>
 */
class User extends AbstractApi
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
     * Get extended information about the authenticated user
     *
     * @link https://ptpkg.com/api/v1/user/
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
     * Update user information's by id. Requires authentication.
     *
     * @link https://ptpkg.com/api/v1/users/
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
