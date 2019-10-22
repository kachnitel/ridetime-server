<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
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
        $entityId = $this->inputFilterId($args['id']);

        return $response->withJson($this->getEndpoint()->get($entityId)->getDetail());
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

        $entity = $this->getEndpoint()->add($data);

        return $response->withJson($entity)->withStatus(201);
    }

    public function list(Request $request, Response $response, array $args): Response
    {
        $ids = $request->getQueryParam('ids')
            ? array_map(function ($id) { return (int) $id; }, $request->getQueryParam('ids'))
            : null;
        $result = $this->getEndpoint()->list($ids);

        return $response->withJson($result);
    }

    /**
     * Returns an EndpointInterface derived class
     *
     * @return EndpointInterface
     */
    abstract protected function getEndpoint();

    protected function inputFilterId($id): int
    {
        return (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    }
}
