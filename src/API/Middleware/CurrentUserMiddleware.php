<?php
namespace RideTimeServer\API\Middleware;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use RideTimeServer\Entities\User;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Exception\UserException;

class CurrentUserMiddleware {
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getMiddleware(bool $requireUser = false)
    {
        $container = $this->container;

        /**
         * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
         * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
         * @param  callable                                 $next     Next middleware
         *
         * @return \Psr\Http\Message\ResponseInterface
         */
        return function (Request $request, Response $response, callable $next) use ($container, $requireUser) {
            $token = $request->getAttribute('token');
            $logger = $container->get('logger');

            if (empty($token['sub'])) {
                throw new RTException('No token found in request');
            }

            /** @var EntityManager $entityManager */
            $entityManager = $container->get('entityManager');

            $user = $entityManager
                ->getRepository(User::class)
                ->findOneBy(['authId' => $token['sub']]);
            if (!$user) {
                if ($requireUser) {
                    throw new UserException('Attempting to access resource without valid user', 400);
                }
                $logger->info('User not found by token[sub] = ' . $token['sub']);
                return $next($request, $response);
            }

            $request = $request->withAttribute('currentUser', $user);
            $response = $next($request, $response);

            return $response;
        };
    }
}
