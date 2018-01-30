<?php

namespace Ammonkc\Ptpkg\Middleware;

use Ammonkc\Ptpkg\Client;
use Ammonkc\Ptpkg\Exception\RuntimeException;
use GuzzleHttp\Client as GuzzleClient;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\RequestInterface;

class OAuth2Middleware
{
    const AUTH_BEARER = 'Bearer %s';
    const AUTH_TOKEN = 'token %s';
    const AUTH_BASIC = 'Basic %s';
    private $tokenOrLogin;
    private $password;
    private $username;
    private $clientSecret;
    private $method;
    private $base_uri = 'https://ptpkg.dev/';
    private $urlAuthorize;
    private $urlAccesstoken;
    private $urlResourceOwnerDetails;
    private $oauthClient;

    public function __construct($tokenOrLogin, $password = null, $method)
    {
        if (is_array($tokenOrLogin)) {
            if ($method == Client::OAUTH_PASSWORD_CREDENTIALS) {
                if (isset($tokenOrLogin['username'])) {
                    $this->username = $tokenOrLogin['username'];
                }
                if (isset($tokenOrLogin['password'])) {
                    $this->password = $tokenOrLogin['password'];
                }
                if (isset($tokenOrLogin['clientSecret'])) {
                    $this->clientSecret = $tokenOrLogin['clientSecret'];
                }
                if (isset($tokenOrLogin['clientId'])) {
                    $this->tokenOrLogin = $tokenOrLogin['clientId'];
                }
            }
            if ($method == Client::OAUTH_CLIENT_CREDENTIALS) {
                if (isset($tokenOrLogin['clientSecret'])) {
                    $this->password = $tokenOrLogin['clientSecret'];
                } elseif (isset($tokenOrLogin['password'])) {
                    $this->password = $tokenOrLogin['password'];
                }
                if (isset($tokenOrLogin['clientId'])) {
                    $this->tokenOrLogin = $tokenOrLogin['clientId'];
                } elseif (isset($tokenOrLogin['username'])) {
                    $this->tokenOrLogin = $tokenOrLogin['username'];
                }
            }
        } else {
            $this->tokenOrLogin = $tokenOrLogin;
            $this->password = $password;
            $this->method = $method;
        }

        $this->oauthClient = new GuzzleClient(['verify' => false]);
        $this->urlAuthorize = $this->base_uri . 'oauth/authorize';
        $this->urlAccesstoken = $this->base_uri . 'oauth/token';
        $this->urlResourceOwnerDetails = $this->base_uri . 'oauth/resource';
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            switch ($this->method) {
                case Client::OAUTH_ACCESS_TOKEN:
                    $request = $request->withHeader('Authorization', sprintf(self::AUTH_BEARER, $this->tokenOrLogin));
                    break;

                case Client::OAUTH_CLIENT_CREDENTIALS:
                    $provider = new GenericProvider([
                        'clientId'                => $this->tokenOrLogin,    // The client ID assigned to you by the provider
                        'clientSecret'            => $this->password,    // The client password assigned to you by the provider
                        'urlAuthorize'            => $this->urlAuthorize,
                        'urlAccessToken'          => $this->urlAccesstoken,
                        'urlResourceOwnerDetails' => null,
                    ], ['httpClient' => $this->oauthClient]);
                    // Try to get an access token using the client credentials grant.
                    $accessToken = $provider->getAccessToken('client_credentials');

                    $request = $request->withHeader('Authorization', sprintf(self::AUTH_BEARER, $accessToken));
                    break;

                case Client::OAUTH_PASSWORD_CREDENTIALS:

                    break;

                default:
                    throw new RuntimeException(sprintf('%s not yet implemented', $this->method));
                    break;
            }

            return $handler($request, $options);
        };
    }
}
