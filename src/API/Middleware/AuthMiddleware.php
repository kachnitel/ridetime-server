<?php
namespace RideTimeServer\API\Middleware;

use PSR\Container\ContainerInterface;
use CoderCat\JWKToPEM\JWKConverter;
use Tuupola\Middleware\JwtAuthentication;

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
            'secure' => false
        ]);
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
