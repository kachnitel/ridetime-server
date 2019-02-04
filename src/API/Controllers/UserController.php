<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\UserEndpoint;

class UserController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $userId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

        $userEndpoint = new UserEndpoint($this->container->entityManager);

        return $response->withJson($userEndpoint->getDetail($userEndpoint->get($userId)));
    }

    public function add(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();

        $userEndpoint = new UserEndpoint($this->container->entityManager);
        $user = $userEndpoint->add($data, $this->container->logger);

        return $response->withJson($user)->withStatus(201);
    }
}
