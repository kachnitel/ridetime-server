<?php
namespace RideTimeServer\API\Middleware;

use PSR\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

use CoderCat\JWKToPEM\JWKConverter;
use Tuupola\Middleware\JwtAuthentication;

use RideTimeServer\Exception\AuthException;

/**
 * TODO: verify 'iss' and 'aud'
 *
 * @uses JwtAuthentication
 * @see https://github.com/tuupola/slim-jwt-auth
 */
class AuthMiddleware
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container, array $config)
    {
        $this->config = $config;
        $this->container = $container;
    }

    /**
     * @return JwtAuthentication
     */
    public function getMiddleware(): JwtAuthentication
    {
        return new JwtAuthentication([
            'secret' => $this->getAuthPublicKey($this->config),
            // 'path' => '/api',
            'algorithm' => ['RS256'],
            'logger' => $this->container['logger'],
            'secure' => false,
            'error' => $this->getErrorHandlerCallback()
        ]);
    }

    protected function getErrorHandlerCallback() {
        return function (ResponseInterface $response, $arguments) {
            $data['status'] = 'error';
            $data['message'] = $arguments['message'];

            throw new AuthException($arguments['message'], $response->getStatusCode() ?? 401);
        };
    }

    /**
     * FIXME: Assuming a lot here
     * Retrieve a JWK from auth api and convert to PEM
     *
     * Possibly replaceable by
     * http://php.net/manual/en/function.openssl-pkey-get-public.php ?
     *
     * @param array $config
     * @return string
     */
    protected function getAuthPublicKey(array $config): string
    {
        // FIXME: fallback etc..Guzzle?
        $keys = json_decode(file_get_contents($config['auth']['publicKeyUrl']), true);
        return (new JWKConverter())->toPEM($keys['keys'][0]);
    }
}
