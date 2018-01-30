<?php

namespace Ammonkc\Ptpkg\TokenService;

use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

/**
 * Bearer PSR7 Middleware
 *
 * @author Gabriel Somoza <gabriel@somoza.me>
 *
 * @see https://tools.ietf.org/html/rfc6750
 */
final class PasswordCredentials extends AbstractTokenService
{
    /** @string Name of the authorization header injected into the request */
    const HEADER_AUTHORIZATION = 'Authorization';

    /** @string Access Token type */
    const TOKEN_TYPE = 'Bearer';

    /** @string */
    const GRANT_PASSWORD_CREDENTIALS = 'password';

    /** @var credentials */
    private $credentials;

    /**
     * @param AbstractProvider $provider A League OAuth2 Client Provider.
     * @param null|AccessToken $accessToken Provide an initial (e.g. cached/persisted) access token.
     * @param null|array $credentials
     * @param null|callable $refreshTokenCallback Will be called with a new AccessToken as a parameter if the Access
     *                                            Token ever needs to be renewed.
     */
    public function __construct(
        AbstractProvider $provider,
        AccessToken $accessToken = null,
        array $credentials = null,
        callable $refreshTokenCallback = null
    ) {
        parent::__construct($provider, $accessToken, $refreshTokenCallback);

        $this->credentials = $credentials;
    }

    /**
     * @inheritdoc
     */
    public function isAuthorized(RequestInterface $request): bool
    {
        return $request->hasHeader(self::HEADER_AUTHORIZATION);
    }

    /**
     * @inheritdoc
     */
    protected function requestAccessToken(): AccessToken
    {
        return $this->getProvider()->getAccessToken(self::GRANT_PASSWORD_CREDENTIALS, $this->credentials);
    }

    /**
     * Returns an authorized copy of the request. Only gets called when necessary (i.e. not if the request is already
     * authorized), and always with a valid (fresh) Access Token. However, it SHOULD be idempotent.
     *
     * @param RequestInterface $request An unauthorized request
     *
     * @return RequestInterface An authorized copy of the request
     */
    protected function getAuthorizedRequest(RequestInterface $request): RequestInterface
    {
        /** @var RequestInterface $request */
        $request = $request->withHeader(
            self::HEADER_AUTHORIZATION,
            $this->getAuthorizationString()
        );

        return $request;
    }

    /**
     * @return string
     */
    private function getAuthorizationString(): string
    {
        return self::TOKEN_TYPE . ' ' . $this->getAccessToken()->getToken();
    }
}
