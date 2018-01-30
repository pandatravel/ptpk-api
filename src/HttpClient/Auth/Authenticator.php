<?php

namespace Ammonkc\Ptpkg\HttpClient\Auth;

use Ammonkc\Ptpkg\Client;
use Ammonkc\Ptpkg\Middleware\OAuth2Middleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use Somoza\OAuth2Middleware\TokenService\Bearer;

class Authenticator
{
    private $client;
    private $tokenOrLogin;
    private $clientSecret;
    private $token;
    private $method;
    private $base_uri = 'https://ptpkg.dev/';
    private $urlAuthorize;
    private $urlAccesstoken;
    private $urlResourceOwnerDetails;
    private $oauthClient;

    public function __construct(ClientInterface $client = null, $tokenOrLogin, $secret = null, $token = null, $method)
    {
        if (is_array($tokenOrLogin)) {
            if ($method == Client::OAUTH_CLIENT_CREDENTIALS) {
                if (isset($tokenOrLogin['clientSecret'])) {
                    $this->clientSecret = $tokenOrLogin['clientSecret'];
                }
                if (isset($tokenOrLogin['token'])) {
                    $this->token = $tokenOrLogin['token'];
                }
                if (isset($tokenOrLogin['clientId'])) {
                    $this->tokenOrLogin = $tokenOrLogin['clientId'];
                }
            }
        } else {
            $this->tokenOrLogin = $tokenOrLogin;
            $this->clientSecret = $secret;
            $this->token = $token;
        }

        $this->client = $client;
        $this->method = $method;
        $this->oauthClient = new GuzzleClient(['verify' => false]);
        $this->urlAuthorize = $this->base_uri . 'oauth/authorize';
        $this->urlAccesstoken = $this->base_uri . 'oauth/token';
        $this->urlResourceOwnerDetails = $this->base_uri . 'oauth/resource';
    }

    public function authorize()
    {
        $provider = new GenericProvider([
            'clientId'                => $this->tokenOrLogin,    // The client ID assigned to you by the provider
            'clientSecret'            => $this->clientSecret,    // The client password assigned to you by the provider
            'urlAuthorize'            => $this->urlAuthorize,
            'urlAccessToken'          => $this->urlAccesstoken,
            'urlResourceOwnerDetails' => null,
        ], ['httpClient' => $this->oauthClient]);

        // attach our oauth2 middleware
        $oauth = new OAuth2Middleware(
            new Bearer($provider), // use the Bearer token type
            [ // ignore (do not attempt to authorize) the following URLs
                $this->urlAuthorize,
                $this->urlResourceOwnerDetails,
            ]
        );
        return $oauth;
    }
}
