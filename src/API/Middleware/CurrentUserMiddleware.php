<?php
namespace RideTimeServer\API\Middleware;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Exception\EntityNotFoundException;

class CurrentUserMiddleware {
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getMiddleware()
    {
        $container = $this->container;

        /**
         * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
         * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
         * @param  callable                                 $next     Next middleware
         *
         * @return \Psr\Http\Message\ResponseInterface
         */
        return function (Request $request, Response $response, callable $next) use ($container) {
            $token = $request->getAttribute('token');
            $logger = $container['logger'];

            if (empty($token['sub'])) {
                throw new RTException('No token found in request');
            }

            $endpoint = new UserEndpoint($container['entityManager'], $logger);
            try {
                $user = $endpoint->findBy('authId', $token['sub']);
            } catch (EntityNotFoundException $e) {
                $logger->info('User not found by token[sub] = ' . $token['sub']);
                return $next($request, $response);
            }

            $request = $request->withAttribute('currentUser', $user);
            $response = $next($request, $response);

            return $response;
        };
    }
}
