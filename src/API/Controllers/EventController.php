<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\EventEndpoint;

class EventController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addMember(Request $request, Response $response, array $args): Response
    {
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

        $data = $request->getParsedBody();
        $userId = (int) filter_var($data['userId'], FILTER_SANITIZE_NUMBER_INT);

        $eventEndpoint = new EventEndpoint($this->container->entityManager);
        $event = $eventEndpoint->get($eventId);

        $result = $eventEndpoint->addEventMember($event, $userId);

        return $response->withJson($result)->withStatus(201);
    }
}
