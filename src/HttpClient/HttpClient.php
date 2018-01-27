<?php

namespace Ammonkc\Ptpkg\HttpClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Ammonkc\Ptpkg\Exception\ErrorException;
use Ammonkc\Ptpkg\Exception\RuntimeException;
use Ammonkc\Ptpkg\Middleware\AuthMiddleware;

/**
 * Performs requests on Ptpkg API
 *
 * @author Ammon Casey <ammon@caseyohana.com>
 */
class HttpClient implements HttpClientInterface
{
    protected $options = [
        'base_url'    => 'https://ptpkg.dev/',

        'user_agent'  => 'ptpkg-api (http://github.com/ammonkc/ptpkg-api)',
        'timeout'     => 10,

        'api_limit'   => 5000,
        'api_version' => 'v1',

        'cache_dir'   => null
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
        $client = $client ?: new GuzzleClient($this->options['base_url'], $this->options);
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
        $request = $this->createRequest($httpMethod, $path, $body, $headers, $options);

        try {
            $response = $this->client->send($request);
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

    protected function createRequest($httpMethod, $path, $body = null, array $headers = [], array $options = [])
    {
        $request = $this->client->createRequest(
            $httpMethod,
            $path,
            array_merge($this->headers, $headers),
            $body,
            $options
        );

        return $request;
    }
}
