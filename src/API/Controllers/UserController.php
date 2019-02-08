<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;

class UserController extends BaseController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function getEndpoint(): EndpointInterface
    {
        return new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
