<?php

namespace Ammonkc\Ptpkg\Middleware;

use Ammonkc\Ptpkg\Client;
use Ammonkc\Ptpkg\Exception\RuntimeException;
use Ammonkc\Ptpkg\TokenService\PasswordCredentials;
use GuzzleHttp\Client as GuzzleClient;
use League\OAuth2\Client\Provider\GenericProvider;
use Somoza\OAuth2Middleware\OAuth2Middleware;
use Somoza\OAuth2Middleware\TokenService\Bearer;
use kamermans\OAuth2\GrantType\ClientCredentials;

// use kamermans\OAuth2\OAuth2Middleware;

class OAuthMiddleware
{
    private $base_uri = 'https://ptpkg.dev/oauth/token';
    private $urlAuthorize = 'https://ptpkg.dev/oauth/authorize';
    private $urlAccesstoken = 'https://ptpkg.dev/oauth/token';
    private $urlResourceOwnerDetails = 'https://ptpkg.dev/oauth/resource';
    private $tokenOrLogin;
    private $client_secret;
    private $password;
    private $username;
    private $method;

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
            }
            if ($method == Client::OAUTH_CLIENT_CREDENTIALS || $method == Client::OAUTH_PASSWORD_CREDENTIALS) {
                if (isset($tokenOrLogin['client_secret'])) {
                    $this->client_secret = $tokenOrLogin['client_secret'];
                } elseif ($method == Client::OAUTH_CLIENT_CREDENTIALS && isset($tokenOrLogin['password'])) {
                    $this->client_secret = $tokenOrLogin['password'];
                }
                if (isset($tokenOrLogin['client_id'])) {
                    $this->tokenOrLogin = $tokenOrLogin['client_id'];
                } elseif ($method == Client::OAUTH_CLIENT_CREDENTIALS && isset($tokenOrLogin['username'])) {
                    $this->tokenOrLogin = $tokenOrLogin['username'];
                }
            }
        } else {
            $this->tokenOrLogin = $tokenOrLogin;
            $this->password = $password;
            $this->method = $method;
        }
    }

    public function getOauthMiddleware()
    {
        switch ($this->method) {
            case Client::OAUTH_ACCESS_TOKEN:

                break;

            case Client::OAUTH_CLIENT_CREDENTIALS:
                // // Authorization client - this is used to request OAuth access tokens
                // $reauth_client = new GuzzleClient([
                //     // URL for access_token request
                //     'base_uri' => $this->base_uri,
                // ]);
                // $reauth_config = [
                //     "client_id" => $this->tokenOrLogin,
                //     "client_secret" => $this->password,
                //     // "scope" => "your scope(s)", // optional
                //     // "state" => time(), // optional
                // ];
                // $grant_type = new ClientCredentials($reauth_client, $reauth_config);
                // $oauth = new OAuth2Middleware($grant_type);

                $provider = new GenericProvider([
                    'clientId' => $this->tokenOrLogin,
                    'clientSecret' => $this->client_secret,
                    'urlAuthorize' => $this->urlAuthorize,
                    'urlAccessToken' => $this->urlAccesstoken,
                    'urlResourceOwnerDetails' => $this->urlResourceOwnerDetails,
                ]);

                // attach our oauth2 middleware
                $oauth = new OAuth2Middleware(
                    new Bearer($provider), // use the Bearer token type
                    [ // ignore (do not attempt to authorize) the following URLs
                        $provider->getBaseAuthorizationUrl(),
                        $provider->getBaseAccessTokenUrl(),
                        $provider->getResourceOwnerDetailsUrl(),
                    ]
                );

                break;

            case Client::OAUTH_PASSWORD_CREDENTIALS:
                $provider = new GenericProvider([
                    'clientId' => $this->tokenOrLogin,
                    'clientSecret' => $this->client_secret,
                    'urlAuthorize' => $this->urlAuthorize,
                    'urlAccessToken' => $this->urlAccesstoken,
                    'urlResourceOwnerDetails' => $this->urlResourceOwnerDetails,
                ]);

                // attach our oauth2 middleware
                $oauth = new OAuth2Middleware(
                    new PasswordCredentials($provider, ['username' => $this->username, 'password' => $this->password]), // use the Bearer token type
                    [ // ignore (do not attempt to authorize) the following URLs
                        $provider->getBaseAuthorizationUrl(),
                        // $provider->getBaseAccessTokenUrl(),
                        // $provider->getResourceOwnerDetailsUrl(),
                    ]
                );

                break;

            default:
                throw new RuntimeException(sprintf('%s not yet implemented', $this->method));
                break;
        }


        return $oauth;
    }
}
