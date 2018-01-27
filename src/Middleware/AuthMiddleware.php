<?php

namespace Ammonkc\Ptpkg\Middleware;

use Psr\Http\Message\RequestInterface;
use Ammonkc\Ptpkg\Client;
use Ammonkc\Ptpkg\Exception\RuntimeException;

class AuthMiddleware
{
    const AUTH_BEARER = 'Bearer %s';
    const AUTH_TOKEN = 'token %s';
    const AUTH_BASIC = 'Basic %s';
    private $tokenOrLogin;
    private $password;
    private $method;

    public function __construct($tokenOrLogin, $password = null, $method)
    {
        $this->tokenOrLogin = $tokenOrLogin;
        $this->password = $password;
        $this->method = $method;
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            switch ($this->method) {
                case Client::AUTH_JWT:
                    $request = $request->withHeader('Authorization', sprintf(self::AUTH_BEARER, $this->tokenOrLogin));
                    break;

                case Client::AUTH_HTTP_TOKEN:
                    $request = $request->withHeader('Authorization', sprintf(self::AUTH_TOKEN, $this->tokenOrLogin));
                    break;

                case Client::AUTH_HTTP_BASIC:
                    $request = $request->withHeader(
                        'Authorization',
                        sprintf(self::AUTH_BASIC, base64_encode($this->tokenOrLogin.':'.$this->password))
                    );
                    break;

                default:
                    throw new RuntimeException(sprintf('%s not yet implemented', $this->method));
                    break;
            }

            return $handler($request, $options);
        };
    }
}
