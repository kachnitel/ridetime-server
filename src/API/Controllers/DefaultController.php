<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\EventEndpoint;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;

class DefaultController
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
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

        $endpoint = $this->getEndpointForEntity($args['entityType']);

        return $response->withJson($endpoint->getDetail($endpoint->get($eventId)));
    }

    public function add(Request $request, Response $response, array $args): Response
    {
        // TODO: Validate input!
        $data = $request->getParsedBody();

        $endpoint = $this->getEndpointForEntity($args['entityType']);
        $event = $endpoint->add($data, $this->container->logger);

        return $response->withJson($event)->withStatus(201);
    }

    protected function getEndpointForEntity(string $type): EndpointInterface
    {
        switch ($type) {
            case 'events':
                $endpoint = new EventEndpoint($this->container->entityManager);
                break;
            case 'users':
                $endpoint = new UserEndpoint($this->container->entityManager);
                break;
        }

        return $endpoint;
    }
}
