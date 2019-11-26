<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use RideTimeServer\Entities\PrimaryEntity;
use Doctrine\ORM\EntityManagerInterface;

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
        $entity = $this->getEndpoint()->get($entityId);

        return $response->withJson((object) [
            'result' => $entity->getDetail(),
            'relatedEntities' => $entity instanceof PrimaryEntity ? $entity->getRelated() : null
        ]);
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
        $currentUser = $request->getAttribute('currentUser');

        /** @var PrimaryEntity $entity */
        $entity = $this->getEndpoint()->add($data, $currentUser);

        return $response->withJson($entity->getDetail())->withStatus(201);
    }

    public function list(Request $request, Response $response, array $args): Response
    {
        $ids = $request->getQueryParam('ids')
            ? array_map(function ($id) { return (int) $id; }, $request->getQueryParam('ids'))
            : null;
        $result = $this->getEndpoint()->list($ids);

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
            // TODO: extract related entities where needed
        ]);
    }

    protected function inputFilterId($id): int
    {
        return (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    }

    protected function extractDetails(array $entities): array
    {
        return array_map(function(PrimaryEntity $entity) {
            return $entity->getDetail();
        }, $entities);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('entityManager');
    }
}
