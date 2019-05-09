<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Endpoints\LocationEndpoint;
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

        return $response->withJson($result);
    }

    public function bbox(Request $request, Response $response, array $args): Response
    {
        $tfEndpoint = new TrailforksEndpoint($this->container['trailforks']);

        $bbox = explode(',', '49.7,-123.1,49.8,-123.2');
        $result = $tfEndpoint->locationsBBox($bbox);

        return $response->withJson($result);
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
