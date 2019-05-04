<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use RideTimeServer\Exception\UserException;
use RideTimeServer\API\Endpoints\Database\NotificationsEndpoint;

class NotificationsController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setToken(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        if (empty($data['token'])) {
            $exception = new UserException('Notifications token missing in request!');
            $exception->setData($data);
            throw $exception;
        }

        $token = $data['token'];
        $user = $request->getAttribute('currentUser');
        $this->getEndpoint()->setToken($user, $token);

        return $response->withStatus(204);
    }

    /**
     * @return NotificationsEndpoint
     */
    protected function getEndpoint()
    {
        return new NotificationsEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
