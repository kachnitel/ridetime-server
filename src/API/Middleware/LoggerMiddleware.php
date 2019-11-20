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
            $startTime = microtime(true);
            $ruStart = getrusage();
            $container->get('logger')->addDebug($request->getMethod() . ' ' . $request->getUri()->getPath());

            $response = $next($request, $response);

            $ruEnd = getrusage();
            $rutime = function ($ru, $rus, $index) {
                return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
                 -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
            };
            $container->get('logger')->addInfo('Request stats', [
                'request' => $request->getMethod() . ' ' . $request->getUri()->getPath(),
                'executionTime' => microtime(true) - $startTime,
                'resources' => [
                    'stime' => $rutime($ruEnd, $ruStart, 'stime') . 'ms',
                    'utime' => $rutime($ruEnd, $ruStart, 'utime') . 'ms'
                ],
                'requestStats' => [
                    'trailforks' => [
                        'count' => $container->get('trailforks')->getRequestCount(),
                        'time' => $container->get('trailforks')->getRequestTime()
                    ]
                ]
            ]);

            return $response;
        };
    }
}
