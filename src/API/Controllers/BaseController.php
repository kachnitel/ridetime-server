<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\EndpointInterface;

abstract class BaseController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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

        $endpoint = $this->getEndpoint();

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

        $endpoint = $this->getEndpoint();
        $event = $endpoint->add($data);

        return $response->withJson($event)->withStatus(201);
    }

    public function list(Request $request, Response $response, array $args): Response
    {
        $endpoint = $this->getEndpoint();

        $result = $endpoint->list();

        return $response->withJson($result);
    }

    /**
     * Returns an EndpointInterface derived class
     *
     * @return EndpointInterface
     */
    abstract protected function getEndpoint(): EndpointInterface;
}
