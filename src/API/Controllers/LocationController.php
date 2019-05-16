<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Endpoints\Database\LocationEndpoint;
use Psr\Http\Message\ServerRequestInterface as Request;
// use Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\Response;
use RideTimeServer\API\Endpoints\RestApi\TrailforksEndpoint;

class LocationController extends BaseController
{
    public function nearby(Request $request, Response $response, array $args): Response
    {
        $tfEndpoint = new TrailforksEndpoint($this->container['trailforks']);

        $latLon = [
            $request->getQueryParams()['lat'],
            $request->getQueryParams()['lon']
        ];
        $range = $request->getQueryParams()['range'];
        $result = $tfEndpoint->locationsNearby($latLon, $range);
        $this->cacheResult($result);

        return $response->withJson($result);
    }

    public function bbox(Request $request, Response $response, array $args): Response
    {
        $tfEndpoint = new TrailforksEndpoint($this->container['trailforks']);

        $bbox = $request->getQueryParams()['coords'];
        $result = $tfEndpoint->locationsBBox($bbox);
        $this->cacheResult($result);

        return $response->withJson($result);
    }

    public function search(Request $request, Response $response, array $args): Response
    {
        $tfEndpoint = new TrailforksEndpoint($this->container['trailforks']);

        $result = $tfEndpoint->locationsSearch($request->getQueryParams()['name']);
        $this->cacheResult($result);

        return $response->withJson($result);
    }

    protected function cacheResult(array $result)
    {
        $this->getEndpoint()->addMultiple($result);
    }

    /**
     * @return LocationEndpoint
     */
    protected function getEndpoint()
    {
        return new LocationEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
