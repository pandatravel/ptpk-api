<?php

namespace Ammonkc\Ptpkg\HttpClient;

use Ammonkc\Ptpkg\Exception\ErrorException;
use Ammonkc\Ptpkg\Exception\RuntimeException;
use Ammonkc\Ptpkg\Middleware\AuthMiddleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Middleware;

/**
 * Performs requests on Ptpkg API
 *
 * @author Ammon Casey <ammon@caseyohana.com>
 */
class HttpClient implements HttpClientInterface
{
    protected $options = [
        'base_uri'    => 'https://ptpkg.dev/',

        'user_agent'  => 'ptpkg-api (http://github.com/ammonkc/ptpkg-api)',
        'timeout'     => 10,

        'api_limit'   => 5000,

        'cache_dir'   => null,
    ];

    protected $requestOptions = [
        'verify'     => false,
    ];

    protected $headers = [];

    private $lastResponse;
    private $lastRequest;

    /**
     * @param array           $options
     * @param ClientInterface $client
     */
    public function __construct(array $options = [], ClientInterface $client = null)
    {
        $this->options = array_merge($this->options, $options);
        $client = $client ?: new GuzzleClient($this->options);
        $this->client  = $client;

        $this->clearHeaders();
    }

    /**
     * {@inheritDoc}
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Clears used headers.
     */
    public function clearHeaders()
    {
        $this->headers = [
            'Accept' => 'application/json',
            'User-Agent' => sprintf('%s', $this->options['user_agent']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, array $parameters = [], array $headers = [])
    {
        return $this->request($path, null, 'GET', $headers, ['query' => $parameters]);
    }

    /**
     * {@inheritDoc}
     */
    public function post($path, $body = null, array $headers = [])
    {
        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function patch($path, $body = null, array $headers = [])
    {
        return $this->request($path, $body, 'PATCH', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path, $body = null, array $headers = [])
    {
        return $this->request($path, $body, 'DELETE', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $body, array $headers = [])
    {
        return $this->request($path, $body, 'PUT', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function request($path, $body = null, $httpMethod = 'GET', array $headers = [], array $options = [])
    {
        $request = $this->createRequest($httpMethod, $path, $body, $headers);
        $options = array_merge($this->requestOptions, $options);

        try {
            $response = $this->client->send($request, $options);
        } catch (\LogicException $e) {
            throw new ErrorException($e->getMessage(), $e->getCode(), $e);
        } catch (\RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->lastRequest  = $request;
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($tokenOrLogin, $password = null, $method)
    {
        $auth = new AuthMiddleware($tokenOrLogin, $password, $method);
        $this->client->getConfig('handler')->push($auth);
    }

    /**
     * {@inheritDoc}
     */
    public function oauth_authenticate($tokenOrLogin, $password = null, $method)
    {
        if (is_array($tokenOrLogin)) {
            if ($method == Client::OAUTH_PASSWORD_CREDENTIALS) {
                if (isset($tokenOrLogin['username'])) {
                    $username = $tokenOrLogin['username'];
                }
                if (isset($tokenOrLogin['password'])) {
                    $password = $tokenOrLogin['password'];
                }
            }
            if ($method == Client::OAUTH_CLIENT_CREDENTIALS || $method == Client::OAUTH_PASSWORD_CREDENTIALS) {
                if (isset($tokenOrLogin['client_secret'])) {
                    $client_secret = $tokenOrLogin['client_secret'];
                } elseif ($method == Client::OAUTH_CLIENT_CREDENTIALS && isset($tokenOrLogin['password'])) {
                    $client_secret = $tokenOrLogin['password'];
                }
                if (isset($tokenOrLogin['client_id'])) {
                    $tokenOrLogin = $tokenOrLogin['client_id'];
                } elseif ($method == Client::OAUTH_CLIENT_CREDENTIALS && isset($tokenOrLogin['username'])) {
                    $tokenOrLogin = $tokenOrLogin['username'];
                }
            }
        }

        $urlAuthorize = $this->options['base_uri'] . 'oauth/authorize';
        $urlAccesstoken = $this->options['base_uri'] . 'oauth/token';
        $urlResourceOwnerDetails = $this->options['base_uri'] . 'oauth/resource';

        switch ($method) {
            case Client::OAUTH_ACCESS_TOKEN:

                break;

            case Client::OAUTH_CLIENT_CREDENTIALS:
                // Authorization client - this is used to request OAuth access tokens
                $reauth_client = new GuzzleClient([
                    // URL for access_token request
                    'base_uri' => $urlAccesstoken,
                    'verify' => false,
                ]);
                $reauth_config = [
                    "client_id" => $tokenOrLogin,
                    "client_secret" => $password,
                    // "scope" => "your scope(s)", // optional
                    // "state" => time(), // optional
                ];
                $grant_type = new ClientCredentials($reauth_client, $reauth_config);
                $oauth = new OAuth2Middleware($grant_type);

                break;

            case Client::OAUTH_PASSWORD_CREDENTIALS:

                break;

            default:
                throw new RuntimeException(sprintf('%s not yet implemented', $method));
                break;
        }

        $this->client->getConfig('handler')->push($oauth);
    }

    /**
     * @return Request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @return Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    protected function createRequest($httpMethod, $path, $body = null, array $headers = [])
    {
        $request = new Request(
            $httpMethod,
            $path,
            array_merge($this->headers, $headers),
            $body
        );

        return $request;
    }
}
