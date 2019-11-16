<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Connectors\TrailforksConnector;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\API\Endpoints\Database\LocationEndpoint;
use RideTimeServer\API\Endpoints\Database\TrailEndpoint;
use RideTimeServer\API\Endpoints\Database\RouteEndpoint;

class LocationController extends BaseController
{
    public function nearby(Request $request, Response $response, array $args): Response
    {
        $latLon = [
            $request->getQueryParam('lat'),
            $request->getQueryParam('lon')
        ];
        $range = $request->getQueryParam('range');

        $result = $this->getEndpoint()->nearby($latLon, $range);

        return $response->withJson($result);
    }

    public function bbox(Request $request, Response $response, array $args): Response
    {
        $bbox = $request->getQueryParam('coords');
        $result = $this->getEndpoint()->bbox($bbox);

        return $response->withJson($result);
    }

    public function search(Request $request, Response $response, array $args): Response
    {
        $name = $request->getQueryParam('name');
        $result = $this->getEndpoint()->search($name);

        return $response->withJson($result);
    }

    public function trailsByLocation(Request $request, Response $response, array $args): Response
    {
        $locationId = $args['id'];

        $responseJson = $this->getTrailEndpoint()->listByLocation($locationId);

        return $response->withJson($responseJson);
    }

    /**
     * @deprecated TODO: remove
     *
     * @param array $result
     * @return array
     */
    protected function cacheResult(array $result): array
    {
        return $this->getEndpoint()->addMultiple($result);
    }

    /**
     * @return LocationEndpoint
     */
    protected function getEndpoint()
    {
        return new LocationEndpoint(
            $this->container->entityManager,
            $this->container->logger,
            $this->getTrailforksConnector()
        );
    }

    protected function getTrailEndpoint(): TrailEndpoint
    {
        return new TrailEndpoint(
            $this->container->entityManager,
            $this->container->logger,
            $this->getTrailforksConnector()
        );
    }

    protected function getRouteEndpoint(): RouteEndpoint
    {
        return new RouteEndpoint(
            $this->container->entityManager,
            $this->container->logger,
            $this->getTrailforksConnector()
        );
    }

    protected function getTrailforksConnector(): TrailforksConnector
    {
        return $this->container['trailforks'];
    }
}
