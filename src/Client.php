<?php

namespace Ammonkc\Ptpkg;

use Ammonkc\Ptpkg\Exception\InvalidArgumentException;
use Ammonkc\Ptpkg\Exception\RuntimeException;
use Ammonkc\Ptpkg\HttpClient\Auth\Authenticator;
use Ammonkc\Ptpkg\HttpClient\HttpClient;
use Ammonkc\Ptpkg\HttpClient\HttpClientInterface;

/**
 * Simple PHP Ptpkg API client.
 *
 * @method Api\CurrentUser currentUser()
 * @method Api\CurrentUser me()
 * @method Api\Tour tour()
 * @method Api\Tour tours()
 *
 * @author Ammon Casey <ammon@caseyohana.com>
 *
 * Website: http://github.com/ammonkc/ptpkg-api
 */
class Client
{
    /**
     * Constant for authentication method. Indicates the new favored login method
     * with username and password via HTTP Authentication.
     */
    const AUTH_HTTP_BASIC = 'http_basic';

    /**
     * Constant for authentication method. Indicates the new login method with
     * with username and token via HTTP Authentication.
     */
    const AUTH_HTTP_TOKEN = 'http_token';

    /**
     * Constant for authentication method. Indicates JSON Web Token
     * authentication required for integration access to the API.
     */
    const AUTH_JWT = 'jwt_token';

    /**
     * Constant for authentication method. Indicates JSON Web Token
     * authentication required for integration access to the API.
     */
    const OAUTH_CLIENT_CREDENTIALS = 'oauth_client';

    /**
     * Constant for authentication method. Indicates JSON Web Token
     * authentication required for integration access to the API.
     */
    const OAUTH_PASSWORD_CREDENTIALS = 'oauth_password';

    /**
     * Constant for authentication method. Indicates JSON Web Token
     * authentication required for integration access to the API.
     */
    const OAUTH_ACCESS_TOKEN = 'oauth_token';

    /**
     * @var array
     */
    private $options = [
        'base_uri'    => 'https://ptpkg.dev/',

        'user_agent'  => 'ptpkg-api (http://github.com/ammonkc/ptpkg-api)',
        'timeout'     => 10,

        'api_limit'   => 5000,

        'cache_dir'   => null
    ];

    /**
     * @var array
     */
    private $endPoints = [];

    /**
     * The Buzz instance used to communicate with Ptpkg.
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Instantiate a new Ptpkg client.
     *
     * @param null|HttpClientInterface $httpClient Ptpkg http client
     */
    public function __construct(array $options = [], HttpClientInterface $httpClient = null)
    {
        $this->options = array_merge($this->options, $options);
        $this->httpClient = $httpClient;
    }

    /**
     * Authenticate a user for all next requests.
     *
     * @param string      $tokenOrLogin Ptpkg private token/username/client ID
     * @param null|string $password     Ptpkg password/secret (optionally can contain $authMethod)
     * @param null|string $authMethod   One of the AUTH_* class constants
     *
     * @throws InvalidArgumentException If no authentication method was given
     */
    public function authenticate($tokenOrLogin, $password = null, $authMethod = null)
    {
        if (null === $password && null === $authMethod) {
            throw new InvalidArgumentException('You need to specify authentication method!');
        }

        if (null === $authMethod && in_array($password, [self::OAUTH_ACCESS_TOKEN, self::OAUTH_CLIENT_CREDENTIALS, self::OAUTH_PASSWORD_CREDENTIALS, self::AUTH_HTTP_BASIC, self::AUTH_JWT, self::AUTH_HTTP_TOKEN])) {
            $authMethod = $password;
            $password   = null;
        }

        if (null === $authMethod) {
            if (is_array($tokenOrLogin) && isset($tokenOrLogin['method'])) {
                $authMethod = $tokenOrLogin['method'];
            } else {
                $authMethod = self::AUTH_HTTP_BASIC;
            }
        }

        if (in_array($authMethod, [self::AUTH_HTTP_BASIC, self::AUTH_JWT, self::AUTH_HTTP_TOKEN])) {
            $this->getHttpClient()->authenticate($tokenOrLogin, $password, $authMethod);
        }

        if (in_array($authMethod, [self::OAUTH_ACCESS_TOKEN, self::OAUTH_CLIENT_CREDENTIALS, self::OAUTH_PASSWORD_CREDENTIALS])) {
            $this->getHttpClient()->oauth_authenticate($tokenOrLogin, $password, $authMethod);
        }
    }

    /**
     * @return HttpClient
     */
    public function authenticateClientCredentials($clientId, $clientSecret = null, $token = null, $method = null)
    {
        $auth = new Authenticator($clientId, $clientSecret, $token, $method);
        $access_token = $auth->authorize();

        return $access_token;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new HttpClient($this->options);
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Clears used headers.
     */
    public function clearHeaders()
    {
        $this->getHttpClient()->clearHeaders();
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->getHttpClient()->setHeaders($headers);
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new InvalidArgumentException(sprintf('Undefined option called: "%s"', $name));
        }

        return $this->options[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function setOption($name, $value)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new InvalidArgumentException(sprintf('Undefined option called: "%s"', $name));
        }

        $this->options[$name] = $value;
    }

    /**
     * @param $endpoint
     * @param array $args
     * @return Endpoint\AbstractWpEndpoint
     */
    public function __call($endpoint, array $args)
    {
        if (!isset($this->endPoints[$endpoint])) {
            $class = 'Ammonkc\\Ptpkg\Api\\' . ucfirst($endpoint);
            if (class_exists($class)) {
                if (! empty($args)) {
                    $this->endPoints[$endpoint] = new $class($this, $args);
                } else {
                    $this->endPoints[$endpoint] = new $class($this);
                }
            } else {
                throw new RuntimeException('Endpoint "' . $endpoint . '" does not exist"');
            }
        }

        return $this->endPoints[$endpoint];
    }
}
