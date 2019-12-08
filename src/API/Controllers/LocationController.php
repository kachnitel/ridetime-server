<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\API\Filters\TrailforksFilter;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\Entities\Route;
use RideTimeServer\Exception\UserException;

class LocationController extends BaseController
{
    public function nearby(Request $request, Response $response, array $args): Response
    {
        $latLon = [
            $request->getQueryParam('lat'),
            $request->getQueryParam('lon')
        ];
        $range = $request->getQueryParam('range');

        $result = $this->getLocationRepository()
            ->nearby($latLon, $range);

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function bbox(Request $request, Response $response, array $args): Response
    {
        $bbox = $request->getQueryParam('coords');
        $result = $this->getLocationRepository()
            ->bbox($bbox);

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function search(Request $request, Response $response, array $args): Response
    {
        $name = $request->getQueryParam('name');
        $result = $this->getLocationRepository()
            ->search($name);

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @deprecated
     */
    public function trailsByLocation(Request $request, Response $response, array $args): Response
    {
        return $this->trails(
            $request->withQueryParams([
                'filter' => [
                    'rid' => $args['id'],
                    'activitytype' => 1
                ]
            ]),
            $response,
            $args
        );
    }

    public function trails(Request $request, Response $response, array $args): Response
    {
        $filters = $request->getQueryParam('filter');
        if (!$filters) {
            throw new UserException('Filters are required for listing trails');
        }
        $filter = $filters ? (new TrailforksFilter($filters))->getTrailforksFilter() : '';

        $result = $this->getTrailRepository()
            ->remoteFilter($filter);

        $this->getEntityManager()->flush();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function routes(Request $request, Response $response, array $args): Response
    {
        $filters = $request->getQueryParam('filter');
        if (!$filters) {
            throw new UserException('Filters are required for listing trails');
        }
        $filter = $filters ? (new TrailforksFilter($filters))->getTrailforksFilter() : '';

        $result = $this->getRouteRepository()
            ->remoteFilter($filter);

        $trails = [];
        $locations = [];
        foreach ($result as $route) {
            $routeTrails = $route->getRelated()->trail;
            $trails = array_unique(array_merge($trails, $routeTrails), SORT_REGULAR);

            if (!in_array($route->getLocation(), $locations)) {
                $locations[] = $route->getLocation();
            }
        }

        $this->getEntityManager()->flush();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result),
            'relatedEntities' => (object) [
                'trail' => $this->extractDetails(array_values($trails)),
                'location' => $this->extractDetails($locations)
            ]
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @deprecated
     */
    public function routesByLocation(Request $request, Response $response, array $args): Response
    {
        return $this->routes(
            $request->withQueryParams([
                'filter' => [
                    'rid' => $args['id'],
                    'activitytype' => 1
                ]
            ]),
            $response,
            $args
        );
    }

    protected function getTrailforksConnector(): TrailforksConnector
    {
        return $this->container->get('trailforks');
    }
}
