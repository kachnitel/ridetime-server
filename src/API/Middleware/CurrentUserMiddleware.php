<?php
namespace RideTimeServer\API\Middleware;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\UserProvider;

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
         * @param \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
         * @param \Psr\Http\Message\ResponseInterface      $response PSR7 response
         * @param callable                                 $next     Next middleware
         *
         * @return \Psr\Http\Message\ResponseInterface
         */
        return function (Request $request, Response $response, callable $next) use ($container) {
            $container['userProvider'] = new UserProvider(
                $container->get('entityManager'),
                $request->getAttribute('token')
            );

            $response = $next($request, $response);

            return $response;
        };
    }
}
