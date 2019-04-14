<?php
namespace RideTimeServer\API\Middleware;

use PSR\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class LoggerMiddleware
{
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
            $container['logger']->addDebug($request->getMethod() . ' ' . $request->getUri()->getPath());

            $response = $next($request, $response);

            return $response;
        };
    }
}
