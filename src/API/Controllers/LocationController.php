<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Connectors\TrailforksConnector;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Route;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\RTException;

class LocationController extends BaseController
{
    public function nearby(Request $request, Response $response, array $args): Response
    {
        $latLon = [
            $request->getQueryParam('lat'),
            $request->getQueryParam('lon')
        ];
        $range = $request->getQueryParam('range');

        $result = $this->getEntityManager()
            ->getRepository(Location::class)
            ->nearby($latLon, $range);

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function bbox(Request $request, Response $response, array $args): Response
    {
        $bbox = $request->getQueryParam('coords');
        $result = $this->getEntityManager()
            ->getRepository(Location::class)
            ->bbox($bbox);

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function search(Request $request, Response $response, array $args): Response
    {
        $name = $request->getQueryParam('name');
        $result = $this->getEntityManager()
            ->getRepository(Location::class)
            ->search($name);

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function trailsByLocation(Request $request, Response $response, array $args): Response
    {
        $locationId = $args['id'];

        $result = $this->getEntityManager()
            ->getRepository(Trail::class)
            ->listByLocation($locationId);
        $this->getEntityManager()->flush();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function routesByLocation(Request $request, Response $response, array $args): Response
    {
        $locationId = $args['id'];

        /** @var Route[] $routes */
        $routes = $this->getEntityManager()
            ->getRepository(Route::class)
            ->listByLocation($locationId);
        $trails = [];
        $locations = [];

        foreach ($routes as $route) {
            $routeTrails = $route->getRelated()->trail;
            $trails = array_unique(array_merge($trails, $routeTrails), SORT_REGULAR);

            if (!in_array($route->getLocation(), $locations)) {
                $locations[] = $route->getLocation();
            }
        }
        $this->getEntityManager()->flush();

        return $response->withJson((object) [
            'results' => $this->extractDetails($routes),
            'relatedEntities' => (object) [
                'trail' => $this->extractDetails(array_values($trails)),
                'location' => $this->extractDetails($locations)
            ]
        ]);
    }

    protected function getTrailforksConnector(): TrailforksConnector
    {
        return $this->container->get('trailforks');
    }
}
