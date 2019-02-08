<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\EndpointInterface;

class DefaultController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Classes have to be within RideTimeServer\API\Endpoints
     */
    const SUPPORTED_ENTITY_ENDPOINTS = [
        'events' => 'EventEndpoint',
        'users' => 'UserEndpoint',
        'locations' => 'LocationEndpoint'
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Response condition$response
     * @param array $args
     * @return Response
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

        $endpoint = $this->getEndpointForEntity($args['entityType']);

        return $response->withJson($endpoint->getDetail($endpoint->get($eventId)));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function add(Request $request, Response $response, array $args): Response
    {
        // TODO: Validate input!
        $data = $request->getParsedBody();

        $endpoint = $this->getEndpointForEntity($args['entityType']);
        $event = $endpoint->add($data, $this->container->logger);

        return $response->withJson($event)->withStatus(201);
    }

    public function list(Request $request, Response $response, array $args): Response
    {
        $endpoint = $this->getEndpointForEntity($args['entityType']);

        $result = $endpoint->list();

        return $response->withJson($result);
    }

    /**
     * Returns an EndpointInterface derived class
     * based on the $type
     *
     * @param string $type oneOf[events|users|locations]
     * @return EndpointInterface
     */
    protected function getEndpointForEntity(string $type): EndpointInterface
    {
        if (!array_key_exists($type, self::SUPPORTED_ENTITY_ENDPOINTS)) {
            throw new \Exception('Endpoint for type ' . $type . ' is not defined');
        }

        $class = '\\RideTimeServer\\API\\Endpoints\\' . self::SUPPORTED_ENTITY_ENDPOINTS[$type];
        $endpoint = new $class(
            $this->container->entityManager,
            $this->container->logger
        );

        return $endpoint;
    }
}
