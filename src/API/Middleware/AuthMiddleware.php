<?php
namespace RideTimeServer\API\Middleware;

use PSR\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

use CoderCat\JWKToPEM\JWKConverter;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Tuupola\Middleware\JwtAuthentication;
use Doctrine\Common\Cache\FilesystemCache;
use GuzzleRetry\GuzzleRetryMiddleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

use function GuzzleHttp\json_decode;

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
            'secret' => $this->getAuthPublicKey(),
            // 'path' => '/api',
            'algorithm' => ['RS256'],
            'logger' => $this->container['logger'],
            'secure' => false,
            'error' => $this->getErrorHandlerCallback()
        ]);
    }

    protected function getErrorHandlerCallback() {
        return function (ResponseInterface $response, $arguments) {
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401)
                ->getBody()->write(json_encode((object) [
                    'status' => 'error',
                    'message' => 'Authentication failed: ' . $arguments['message'],
                    'code' => 401
                ]));
        };
    }

    /**
     * Retrieve a JWK from auth api and convert to PEM
     *
     * Possibly replaceable by
     * http://php.net/manual/en/function.openssl-pkey-get-public.php ?
     *
     * @return string
     */
    protected function getAuthPublicKey(): string
    {
        $stack = HandlerStack::create();

        $stack->push(GuzzleRetryMiddleware::factory());
        $stack->push(new CacheMiddleware(
            new GreedyCacheStrategy(
              new DoctrineCacheStorage(
                new FilesystemCache('/tmp/')
              ),
              $this->config['auth']['cacheTtl']
            )
          ), 'cache');

        $client = new Client(['handler' => $stack]);

        $keys = json_decode($client->get($this->config['auth']['publicKeyUrl'])->getBody(), true);
        return (new JWKConverter())->toPEM($keys['keys'][0]);
    }
}
