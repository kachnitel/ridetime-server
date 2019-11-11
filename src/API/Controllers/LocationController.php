<?php
namespace RideTimeServer\API\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\API\Endpoints\Database\LocationEndpoint;
use RideTimeServer\API\Endpoints\Database\TrailEndpoint;
use RideTimeServer\API\Endpoints\RestApi\TrailforksEndpoint;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Trail;

class LocationController extends BaseController
{
    public function nearby(Request $request, Response $response, array $args): Response
    {
        $tfEndpoint = $this->getTrailforksEndpoint();

        $latLon = [
            $request->getQueryParam('lat'),
            $request->getQueryParam('lon')
        ];
        $range = $request->getQueryParam('range');
        $result = $tfEndpoint->locationsNearby($latLon, $range);
        $responseJson = $this->cacheResult($result);

        return $response->withJson($responseJson);
    }

    public function bbox(Request $request, Response $response, array $args): Response
    {
        $tfEndpoint = $this->getTrailforksEndpoint();

        $bbox = $request->getQueryParam('coords');
        $result = $tfEndpoint->locationsBBox($bbox);
        $responseJson = $this->cacheResult($result);

        return $response->withJson($responseJson);
    }

    public function search(Request $request, Response $response, array $args): Response
    {
        $tfEndpoint = $this->getTrailforksEndpoint();

        $result = $tfEndpoint->locationsSearch($request->getQueryParam('name'));
        $responseJson = $this->cacheResult($result);

        return $response->withJson($responseJson);
    }

    public function trailsByLocation(Request $request, Response $response, array $args): Response
    {
        $locationId = $args['id'];

        $responseJson = $this->getTrailEndpoint()->listByLocation($locationId);

        return $response->withJson($responseJson);
    }

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
            $this->getTrailforksEndpoint()
        );
    }

    protected function getTrailEndpoint(): TrailEndpoint
    {
        return new TrailEndpoint(
            $this->container->entityManager,
            $this->container->logger,
            $this->getTrailforksEndpoint()
        );
    }

    protected function getTrailforksEndpoint(): TrailforksEndpoint
    {
        return new TrailforksEndpoint($this->container['trailforks']);
    }
}
